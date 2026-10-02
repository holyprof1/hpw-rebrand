<?php
/**
 * Replace the stored text and search descriptions of the supporting pages (CLI only).
 *   php tools/update-trust-pages.php [--apply]
 * About, Editorial Policy and Corrections render from theme copy (inc/trust.php); their stored text only needs
 * to be consistent, and their excerpt is used as the meta description. Privacy, Disclaimer, Advertise and Work
 * With Us render the stored text, so the real copy lives here (version controlled).
 * The owner should read and approve this wording, in particular the privacy statements.
 */
if ( 'cli' !== php_sapi_name() ) { exit( 'CLI only' ); }
$apply = in_array( '--apply', $argv, true );
require dirname( __DIR__, 4 ) . '/wp-load.php';
remove_all_actions( 'transition_post_status' ); remove_all_actions( 'save_post' ); remove_all_actions( 'post_updated' );
global $wpdb;

$pages = array(
    'about' => array(
        'excerpt' => 'Holyprofweb finds new apps, websites, products, people and internet trends early, researches them properly and explains them clearly.',
        'content' => '<p>Holyprofweb finds new apps, websites, products, people and internet trends early, researches them properly and explains them clearly.</p>',
    ),
    'editorial-policy' => array(
        'excerpt' => 'The standards every Holyprofweb article is held to: sourcing, verification, claims about named people and companies, use of AI tools and corrections.',
        'content' => '<p>The standards every Holyprofweb article is held to.</p>',
    ),
    'corrections-updates-policy' => array(
        'excerpt' => 'How Holyprofweb handles mistakes: how to report one, what we do about it and when an article shows an updated date.',
        'content' => '<p>If we publish something wrong, we fix it and say so.</p>',
    ),
    'disclaimer' => array(
        'excerpt' => 'Holyprofweb publishes general information, not professional advice. What that means for how you use the site.',
        'content' => '<p>Holyprofweb publishes general information for readers. It is not legal, financial, medical, tax or other professional advice, and you should not rely on it as a substitute for advice from a qualified professional.</p>
<h2>Accuracy</h2>
<p>We research each article from named sources and say when we could not verify something. Facts about companies, products and prices change, so an article reflects what we found on its published or updated date. If you spot an error, tell us through the <a href="/contact/">contact page</a> and see our <a href="/corrections-updates-policy/">Corrections Policy</a>.</p>
<h2>No affiliation</h2>
<p>Unless an article says otherwise, Holyprofweb is not affiliated with, endorsed by or connected to the companies, products, apps, websites or people it writes about. Names and logos belong to their owners.</p>
<h2>Prices, availability and specifications</h2>
<p>Figures such as prices, fees, specifications and release dates are taken from the sources linked in each article and may differ by country, seller or date. Check the official source before you buy, sign up or pay.</p>
<h2>Third-party links</h2>
<p>We link to other websites so you can check our sources. We do not control those sites and are not responsible for their content or practices.</p>
<h2>Your own checks</h2>
<p>Decisions about money, health, legal matters, work and personal safety are yours. Do your own checks and speak to a qualified professional where it matters.</p>',
    ),
    'privacy-policy' => array(
        'excerpt' => 'How Holyprofweb handles personal data: comments, contact messages, cookies, server logs and the choices you have.',
        'content' => '<p>This page explains what personal data Holyprofweb (holyprofweb.com) handles and why. It was last reviewed on 2 October 2026.</p>
<h2>Who we are</h2>
<p>Holyprofweb is an independent online publication. For privacy questions, write to <a href="mailto:admin@holyprofweb.com">admin@holyprofweb.com</a> or use the <a href="/contact/">contact page</a>.</p>
<h2>What we collect and why</h2>
<ul>
<li><strong>Contact messages.</strong> If you use the contact form we receive your name, email address and message so we can reply. Messages are delivered to our mailbox and kept there for as long as we need them to deal with your request and for our records of corrections.</li>
<li><strong>Comments.</strong> If comments are open on an article and you leave one, we store the name, email address and comment you enter, plus your IP address and browser user-agent string to help detect spam. Your email address is not shown publicly. A hashed form of your email address may be sent to Gravatar to see whether you have a profile picture.</li>
<li><strong>Server logs.</strong> Our web host records technical information such as IP address, requested page, date and browser type for security and troubleshooting. We do not use these logs to profile readers.</li>
<li><strong>Search.</strong> The site search runs on our own server. We do not sell or share what you search for.</li>
</ul>
<h2>Cookies and local storage</h2>
<p>If you leave a comment you can choose to save your name, email address and website in cookies for one year so you do not have to type them again. The site also stores your light or dark display choice in your browser so it can remember it. Logged-in editors receive standard WordPress login cookies. At the time of writing we do not run advertising networks or analytics tracking scripts on the site. If that changes, we will update this page first.</p>
<h2>Who we share data with</h2>
<p>We do not sell personal data. Our hosting provider processes data on our behalf to serve the site and deliver email. Gravatar (Automattic) serves author and commenter profile pictures, so your browser contacts its servers when one is shown, and it receives the hashed email address described above for comment pictures; its policy is at <a href="https://automattic.com/privacy/" rel="noopener nofollow">automattic.com/privacy</a>. We may disclose information where the law requires it.</p>
<h2>Your choices</h2>
<p>You can ask us to tell you what we hold about you, to correct it, or to delete it, including a comment or a contact message, by writing to the address above. We may keep information we are legally required to keep. You can also clear cookies in your browser at any time.</p>
<h2>Links to other sites</h2>
<p>Articles link to other websites. We are not responsible for their privacy practices; read their policies.</p>
<h2>Children</h2>
<p>The site is not directed to children under 13 and we do not knowingly collect their personal data.</p>
<h2>Changes</h2>
<p>We will update this page when our practices change and revise the review date above.</p>',
    ),
    'advertise' => array(
        'excerpt' => 'Advertising and sponsorship on Holyprofweb: what we accept, how sponsored content is labelled and how to get in touch.',
        'content' => '<p>Holyprofweb is an independent publication and does not currently run advertising networks on its pages.</p>
<h2>Sponsorship enquiries</h2>
<p>If you would like to discuss a sponsorship, send the details to <a href="mailto:admin@holyprofweb.com">admin@holyprofweb.com</a> with the subject line "Sponsorship enquiry". Include who you are, what you want to promote and your audience.</p>
<h2>Our rules</h2>
<ul>
<li>Payment does not buy coverage, and it does not change what an article says.</li>
<li>Any sponsored content or affiliate link would be clearly labelled on the page.</li>
<li>We do not accept sponsorship for topics where a reader could be harmed by a misleading claim, such as health or financial products, without our own independent review.</li>
</ul>
<p>See the <a href="/editorial-policy/">Editorial Policy</a> for how we treat sponsored content.</p>',
    ),
    'work-with-us' => array(
        'excerpt' => 'Pitch Holyprofweb a story: what we look for in new apps, websites, products, people and internet trends, and how to send it.',
        'content' => '<p>Holyprofweb is a small publication. We occasionally work with writers and researchers who can find and explain something new online accurately.</p>
<h2>What we look for</h2>
<ul>
<li>A new or newly relevant app, website, product, founder or trend that existing search results explain poorly.</li>
<li>Claims backed by named, primary sources, with anything unverified labelled as such.</li>
<li>Original screenshots or hands-on testing where you have actually used the product.</li>
</ul>
<h2>How to pitch</h2>
<p>Email <a href="mailto:admin@holyprofweb.com">admin@holyprofweb.com</a> with the subject line "Pitch" and send the topic, why it matters now, your sources and one writing sample. We reply to pitches we can use.</p>',
    ),
);

foreach ( $pages as $slug => $v ) {
    $p = get_page_by_path( $slug, OBJECT, 'page' );
    if ( ! $p ) { echo "MISSING $slug\n"; continue; }
    echo $apply ? 'UPDATE ' : 'DRY ', $slug, ' (', $p->ID, ")\n";
    if ( $apply ) {
        $wpdb->update( $wpdb->posts, array( 'post_content' => $v['content'], 'post_excerpt' => $v['excerpt'] ), array( 'ID' => $p->ID ) );
        clean_post_cache( $p->ID );
    }
}
