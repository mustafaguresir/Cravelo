<?php
declare(strict_types=1);

namespace App\Exception;

use DomainException;

/**
 * Geçersiz sipariş durum geçişi veya hazırlık sonrası iptal denemesi istisnası.
 * DomainException miras aldığı için mevcut tüm alan istisnası kontrolleriyle %100 uyumludur.
 */
final class InvalidOrderTransitionException extends DomainException
{
}
