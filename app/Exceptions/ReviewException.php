<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Kesalahan aturan ulasan (kelayakan, duplikat, foto, moderasi).
 * Pesannya ramah dan berbahasa Indonesia, aman ditampilkan ke pelanggan/staf.
 */
class ReviewException extends RuntimeException {}
