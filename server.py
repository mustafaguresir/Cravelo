import os
import sys
import json
import urllib.parse
from http.server import HTTPServer, SimpleHTTPRequestHandler

PORT = 8000
DIRECTORY = os.path.join(os.path.dirname(os.path.abspath(__file__)), "public")

class CraveloHandler(SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=DIRECTORY, **kwargs)

    def end_headers(self):
        self.send_header('Cache-Control', 'no-cache, no-store, must-revalidate')
        self.send_header('Pragma', 'no-cache')
        self.send_header('Expires', '0')
        super().end_headers()

    def do_POST(self):
        content_length = int(self.headers.get('Content-Length', 0))
        post_data = self.rfile.read(content_length).decode('utf-8', errors='ignore')
        params = urllib.parse.parse_qs(post_data)

        # 1. /login veya /index.php POST isteği (303 PRG Akışı)
        clean_path = self.path.split('?')[0]
        if clean_path in ['/login', '/login.html', '/index.php', '/register', '/register.html']:
            username = params.get('username', [''])[0].strip()
            password = params.get('password', [''])[0].strip()

            # 303 See Other ile ana sayfaya yönlendir (PRG Deseni)
            self.send_response(303)
            self.send_header('Location', '/index.html')
            self.end_headers()
            return

        # 2. /logout POST isteği
        if clean_path in ['/logout', '/logout.php', '/logout.html']:
            self.send_response(303)
            self.send_header('Location', '/login.html')
            self.end_headers()
            return

        # 3. Genel API veya diğer POST istekleri için 200 OK yanıtı
        self.send_response(200)
        self.send_header('Content-Type', 'application/json; charset=utf-8')
        self.end_headers()
        self.wfile.write(b'{"status":"ok"}')

    def do_GET(self):
        clean_path = self.path.split('?')[0]
        if clean_path in ['/login', '/register']:
            self.path = '/login.html'
        elif clean_path == '/logout':
            self.path = '/logout.html'
        elif clean_path == '/':
            self.path = '/index.html'
        return super().do_GET()

if __name__ == '__main__':
    server = HTTPServer(('0.0.0.0', PORT), CraveloHandler)
    print(f"Cravelo Web Sunucusu aktif: http://localhost:{PORT}", flush=True)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass
