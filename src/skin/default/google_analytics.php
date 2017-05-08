<?php
use Sprout\Helpers\AdminAuth;


$analytics_id = Kohana::config('sprout.google_analytics_id');

if (!IN_PRODUCTION) {
    // Test server - trace output
    echo "<script type=\"text/javascript\">function ga(){ console.log('Google Analytics', arguments); }</script>\n";
    return;

} else if (!$analytics_id or AdminAuth::isLoggedIn()) {
    // Don't log analytics for admin users or if no config
    return;
}
?>

<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', '<?php echo $analytics_id; ?>', 'auto');
ga('send', 'pageview');
</script>
