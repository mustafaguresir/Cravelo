<?php
declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

/**
 * Bölgede siparişi üstlenebilecek müsait kurye bulunamadığında fırlatılan istisna.
 * RuntimeException miras aldığı için mevcut tüm çalışma zamanı kontrolleriyle %100 uyumludur.
 */
final class NoAvailableCourierException extends RuntimeException
{
}
