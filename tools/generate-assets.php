<?php
// Generates solid-color placeholder PNGs for the WordPress.org plugin directory.
// Run from the plugin root. Requires the zlib extension (always available in PHP 8).

function make_png( $width, $height, $r, $g, $b, $path ) {
	$raw = '';
	$row = chr(0); // filter byte
	for ( $x = 0; $x < $width; $x++ ) {
		$row .= chr( $r ) . chr( $g ) . chr( $b );
	}
	for ( $y = 0; $y < $height; $y++ ) {
		$raw .= $row;
	}

	$signature = "\x89PNG\r\n\x1a\n";

	$ihdr = pack( 'N', 13 ) . 'IHDR' . pack( 'N', $width ) . pack( 'N', $height ) . chr( 8 ) . chr( 2 ) . chr( 0 ) . chr( 0 ) . chr( 0 );
	$ihdr .= pack( 'N', crc32( $ihdr ) );

	$idat = pack( 'N', strlen( $raw ) ) . 'IDAT' . zlib_encode( $raw, ZLIB_ENCODING_RAW );
	$idat .= pack( 'N', crc32( substr( $idat, 0, -4 ) ) );

	$iend = pack( 'N', 0 ) . 'IEND' . pack( 'N', crc32( 'IEND' ) );

	file_put_contents( $path, $signature . $ihdr . $idat . $iend );
}

$base = dirname( __DIR__ ) . '/assets';
@mkdir( $base, 0755, true );

make_png( 128, 128, 38, 94, 140, $base . '/icon-128x128.png' );
make_png( 256, 256, 38, 94, 140, $base . '/icon-256x256.png' );
make_png( 772, 250, 28, 40, 60, $base . '/banner-772x250.png' );
make_png( 1544, 500, 28, 40, 60, $base . '/banner-1544x500.png' );
make_png( 1200, 675, 245, 245, 247, $base . '/screenshot-1.png' );

echo "Assets generated.\n";
