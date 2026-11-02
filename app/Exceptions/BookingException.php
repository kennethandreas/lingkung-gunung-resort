<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Kesalahan aturan bisnis booking/pembayaran.
 * Pesannya ramah dan berbahasa Indonesia, aman ditampilkan ke tamu/staf.
 */
class BookingException extends RuntimeException {}
