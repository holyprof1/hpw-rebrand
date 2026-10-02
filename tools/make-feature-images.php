<?php
/**
 * Renders a Holyprofweb editorial feature graphic (1200x675 WebP) for every tools/recovery/<id>.json.
 *
 *   php tools/make-feature-images.php [--chrome="C:/Program Files/Google/Chrome/Application/chrome.exe"] [--only=ID,ID] [--force]
 *
 * Design: section palette (Apps & Websites = ink, Products & Tech = paper, People = gold, Internet Trends = deep ink with
 * a gold rule), the entity name set large in Inter, the section as a kicker, the official hostname, and the
 * Holyprofweb mark. A decorative pattern (grid, rings or bars) is chosen from the name so pages are not identical.
 * Nothing here pretends to be a screenshot or a product image.
 */
if ( 'cli' !== php_sapi_name() ) {
    exit( 'CLI only' );
}
$opts   = getopt( '', array( 'chrome::', 'only::', 'force' ) );
$chrome = $opts['chrome'] ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe';
$only   = isset( $opts['only'] ) ? array_map( 'intval', explode( ',', $opts['only'] ) ) : array();
$dir    = __DIR__ . '/recovery';
$imgdir = $dir . '/images';
@mkdir( $imgdir, 0775, true );
$tmpdir = sys_get_temp_dir() . '/hpw-gfx';
@mkdir( $tmpdir, 0775, true );
$font   = 'file:///' . str_replace( '\\', '/', realpath( __DIR__ . '/../assets/fonts/inter-latin-var.woff2' ) );

$palette = array(
    'apps-websites'   => array( 'bg' => '#0d0d0f', 'fg' => '#f4f2ec', 'muted' => '#9c9a93', 'accent' => '#f0a500', 'label' => 'Apps & Websites' ),
    'products-tech'   => array( 'bg' => '#f6f3ea', 'fg' => '#0d0d0f', 'muted' => '#6a675f', 'accent' => '#f0a500', 'label' => 'Products & Tech' ),
    'people'          => array( 'bg' => '#f0a500', 'fg' => '#0d0d0f', 'muted' => '#4a3a10', 'accent' => '#0d0d0f', 'label' => 'People' ),
    'internet-trends' => array( 'bg' => '#14151a', 'fg' => '#f4f2ec', 'muted' => '#a4a29b', 'accent' => '#f0a500', 'label' => 'Internet Trends' ),
);

function hpw_gfx_pattern( $n, $accent, $dark ) {
    $a = $accent;
    $op = $dark ? '0.14' : '0.16';
    switch ( $n % 3 ) {
        case 0: // grid
            $lines = '';
            for ( $x = 600; $x <= 1200; $x += 75 ) { $lines .= "<line x1='$x' y1='0' x2='$x' y2='675' />"; }
            for ( $y = 0; $y <= 675; $y += 75 ) { $lines .= "<line x1='600' y1='$y' x2='1200' y2='$y' />"; }
            return "<svg viewBox='0 0 1200 675' preserveAspectRatio='none' style='position:absolute;inset:0;width:100%;height:100%'><g stroke='$a' stroke-opacity='$op' stroke-width='2'>$lines</g></svg>";
        case 1: // rings
            return "<svg viewBox='0 0 1200 675' style='position:absolute;inset:0;width:100%;height:100%'><g fill='none' stroke='$a' stroke-opacity='$op' stroke-width='3'><circle cx='980' cy='250' r='90'/><circle cx='980' cy='250' r='170'/><circle cx='980' cy='250' r='250'/><circle cx='980' cy='250' r='330'/></g></svg>";
        default: // bars
            $bars = '';
            for ( $i = 0; $i < 9; $i++ ) { $x = 700 + $i * 62; $h = 120 + ( ( $i * 53 ) % 5 ) * 70; $bars .= "<rect x='$x' y='" . ( 675 - $h ) . "' width='36' height='$h'/>"; }
            return "<svg viewBox='0 0 1200 675' style='position:absolute;inset:0;width:100%;height:100%'><g fill='$a' fill-opacity='$op'>$bars</g></svg>";
    }
}

foreach ( glob( $dir . '/*.json' ) as $file ) {
    $d  = json_decode( file_get_contents( $file ), true );
    $id = (int) ( $d['post_id'] ?? 0 );
    if ( ! $id || ( $only && ! in_array( $id, $only, true ) ) ) { continue; }
    $out = "$imgdir/$id.webp";
    if ( file_exists( $out ) && ! isset( $opts['force'] ) ) { continue; }
    $p     = $palette[ $d['section'] ];
    $name  = $d['facts']['name'] ?: $d['title'];
    $host  = preg_replace( '#^www\.#', '', (string) parse_url( (string) ( $d['facts']['url'] ?? '' ), PHP_URL_HOST ) );
    $len   = mb_strlen( $name );
    $size  = $len <= 10 ? 170 : ( $len <= 16 ? 132 : ( $len <= 24 ? 100 : 78 ) );
    $dark  = in_array( $d['section'], array( 'apps-websites', 'internet-trends' ), true );
    $pat   = hpw_gfx_pattern( crc32( $name ), $p['accent'], $dark );
    $mark  = "<svg width='34' height='34' viewBox='0 0 64 64'><path d='M32 4 10 12v16c0 14 10 24 22 32 12-8 22-18 22-32V12L32 4Z' fill='{$p['fg']}'/><path d='m20 32 8 8 16-18' fill='none' stroke='#F0A500' stroke-width='6' stroke-linecap='round' stroke-linejoin='round'/></svg>";
    $html  = "<!doctype html><meta charset='utf-8'><style>@font-face{font-family:Inter;src:url('$font');font-weight:100 900}*{box-sizing:border-box;margin:0}html,body{width:1200px;height:675px;overflow:hidden}body{position:relative;background:{$p['bg']};color:{$p['fg']};font-family:Inter,Arial,sans-serif}.k{position:absolute;left:72px;top:70px;font-size:26px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:{$p['accent']}}.rule{position:absolute;left:72px;top:122px;width:96px;height:8px;background:{$p['accent']}}.n{position:absolute;left:72px;right:300px;top:170px;bottom:150px;display:flex;align-items:center;font-size:{$size}px;font-weight:800;letter-spacing:-0.045em;line-height:1.0;overflow-wrap:anywhere}.h{position:absolute;left:72px;bottom:64px;font-size:30px;font-weight:600;color:{$p['muted']}}.b{position:absolute;right:72px;bottom:60px;display:flex;align-items:center;gap:12px;font-size:30px;font-weight:800;letter-spacing:-.03em}.b span{color:{$p['accent']}}</style>" . $pat
           . "<div class='k'>" . htmlspecialchars( $p['label'] ) . "</div><div class='rule'></div><div class='n'>" . htmlspecialchars( $name ) . "</div><div class='h'>" . htmlspecialchars( $host ) . "</div><div class='b'>$mark<div>Holyprof<span>web</span></div></div>";
    $tmpHtml = "$tmpdir/$id.html";
    $tmpPng  = "$tmpdir/$id.png";
    file_put_contents( $tmpHtml, $html );
    @unlink( $tmpPng );
    $cmd = '"' . $chrome . '" --headless=new --disable-gpu --hide-scrollbars --force-device-scale-factor=1 --window-size=1200,675 --virtual-time-budget=3000 --user-data-dir="' . $tmpdir . '/prof" --screenshot="' . $tmpPng . '" "file:///' . str_replace( '\\', '/', $tmpHtml ) . '" 2>NUL';
    exec( $cmd );
    if ( ! file_exists( $tmpPng ) ) { echo "FAILED $id\n"; continue; }
    $im = imagecreatefrompng( $tmpPng );
    if ( imagesx( $im ) !== 1200 ) {
        $r = imagecreatetruecolor( 1200, 675 );
        imagecopyresampled( $r, $im, 0, 0, 0, 0, 1200, 675, imagesx( $im ), imagesy( $im ) );
        $im = $r;
    }
    imagewebp( $im, $out, 82 );
    echo "OK $id " . round( filesize( $out ) / 1024 ) . "KB  $name\n";
}
