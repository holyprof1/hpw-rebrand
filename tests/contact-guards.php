<?php
// Isolated checks: no WordPress database, network calls or real emails.
define('ABSPATH', __DIR__);
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);
define('DAY_IN_SECONDS', 86400);
class WP_Error { public $code; public function __construct($code,$message='') { $this->code=$code; } public function get_error_message() { return $this->code; } }
function add_action(...$a) {} function add_filter(...$a) {}
function sanitize_key($s) { return preg_replace('/[^a-z0-9_\-]/','',strtolower($s)); }
function sanitize_text_field($s) { return trim(strip_tags($s)); }
function wp_unslash($s) { return $s; }
function wp_salt($s) { return 'isolated-fixture-'.$s; }
function __($s,$domain='') { return $s; }
function get_option($k,$default=null) { return $default; }
function wp_parse_url($s,$component=-1) { return parse_url($s,$component); }
function home_url($s='') { return 'https://holyprofweb.com'.$s; }
function get_transient($key) { return $GLOBALS['transients'][$key]??false; }
function set_transient($key,$value,$ttl) { $GLOBALS['transients'][$key]=$value; }
function check($condition,$label) { if(!$condition) { throw new Exception($label); } echo "PASS $label\n"; }
require (getenv('HPW_TEST_THEME_ROOT') ?: dirname(__DIR__)).'/inc/security-hardening.php';
$_SERVER['REMOTE_ADDR']='198.51.100.4'; $_SERVER['HTTP_USER_AGENT']='first';
$_SERVER['HTTP_ORIGIN']='https://holyprofweb.com';
$issued=time()-6;
$_POST=['hpw_contact_time'=>$issued,'hpw_contact_signature'=>hash_hmac('sha256','hpw_contact|'.$issued,wp_salt('nonce')),'hpw_contact_website'=>''];
check(true===holyprofweb_validate_public_form_guard('contact'),'valid signed form passes');
$valid=$_POST;
$_POST['hpw_contact_time']=time()-8;
check('spam_signature'===holyprofweb_validate_public_form_guard('contact')->code,'forged timestamp is rejected');
$_POST=$valid; unset($_POST['hpw_contact_signature']);
check('spam_signature'===holyprofweb_validate_public_form_guard('contact')->code,'missing signature is rejected');
$_POST=$valid; $_POST['hpw_contact_website']='spam';
check('spam_honeypot'===holyprofweb_validate_public_form_guard('contact')->code,'honeypot is rejected');
$_POST=$valid; $_SERVER['HTTP_ORIGIN']='https://attacker.invalid';
check('bad_origin'===holyprofweb_validate_public_form_guard('contact')->code,'cross-site request is rejected');
$_SERVER['HTTP_ORIGIN']='https://holyprofweb.com';
check('spam_signature'===holyprofweb_validate_public_form_guard('comment')->code,'token cannot be reused for another form');
$key=holyprofweb_get_rate_limit_key('contact'); $_SERVER['HTTP_USER_AGENT']='different';
check($key===holyprofweb_get_rate_limit_key('contact'),'changing User-Agent preserves rate limit');
$_SERVER['REMOTE_ADDR']='198.51.100.5';
check($key!==holyprofweb_get_rate_limit_key('contact'),'separate IP gets separate allowance');
$GLOBALS['transients']=[];
for($i=0;$i<3;$i++)check(true===holyprofweb_enforce_rate_limit('contact',3,3600,'limited'),'allowed attempt '.($i+1));
check('rate_limited'===holyprofweb_enforce_rate_limit('contact',3,3600,'limited')->code,'fourth attempt is rejected');

// Run the contact handler itself with a stub mail transport to check delivery decisions.
function get_header() {} function nocache_headers() {} function do_action(...$args) {}
function wp_verify_nonce($nonce,$action) { return $nonce==='fixture-nonce'; }
function is_wp_error($v) { return $v instanceof WP_Error; }
function sanitize_email($s) { return trim($s); }
function sanitize_textarea_field($s) { return trim(strip_tags($s)); }
function wp_strip_all_tags($s) { return strip_tags($s); }
function is_email($s) { return filter_var($s,FILTER_VALIDATE_EMAIL)!==false; }
function wp_mail(...$args) { $GLOBALS['mail_calls']++; return true; }
function contact_fixture($extra=[]) {
    $GLOBALS['transients']=[]; $GLOBALS['mail_calls']=0;
    $_SERVER['HTTP_ORIGIN']='https://holyprofweb.com';
    $issued=time()-8;
    $_POST=array_merge(['hpw_contact_nonce'=>'fixture-nonce','contact_name'=>'Ada','contact_email'=>'ada@example.invalid','contact_subject'=>'Content Correction','contact_message'=>'Please correct the release date in the article. My source is the official announcement.','hpw_contact_time'=>$issued,'hpw_contact_signature'=>hash_hmac('sha256','hpw_contact|'.$issued,wp_salt('nonce')),'hpw_contact_website'=>''],$extra);
    $file=(getenv('HPW_TEST_THEME_ROOT') ?: dirname(__DIR__)).'/page-contact.php';
    $source=file_get_contents($file); $handler=substr($source,5,strpos($source,'?>')-5);
    eval($handler);
    return ['success'=>$success,'errors'=>$errors,'mail'=>$GLOBALS['mail_calls']];
}
$r=contact_fixture(); check($r['success']&&$r['mail']===1,'legitimate enquiry reaches stub mail exactly once');
$r=contact_fixture(['contact_name'=>'Robertkib']);check(!$r['success']&&$r['mail']===0,'confirmed spam name never reaches mail');
$r=contact_fixture(['hpw_contact_signature'=>'forged']);check(!$r['success']&&$r['mail']===0,'forged form never reaches mail');
$r=contact_fixture(['hpw_contact_website'=>'bot']);check(!$r['success']&&$r['mail']===0,'honeypot never reaches mail');
$r=contact_fixture(['hpw_contact_nonce'=>'invalid']);check(!$r['success']&&$r['mail']===0,'invalid nonce never reaches mail');
