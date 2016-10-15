<?php

require('config.inc.php');

if (php_sapi_name() != "cli") {
echo 'Only for cronjob';
exit;
}
	date_default_timezone_set('UTC');
   class MyDB extends SQLite3
   {
      function __construct()
      {
         $this->open(ZEROMEDB_LOCATION);
      }
   }
   $db = new MyDB();
   if(!$db){
      echo $db->lastErrorMsg();
	  exit;
   }

   $sql =<<<EOF
   SELECT `post`.`body` , `post`.`date_added` , `post`.`post_id` , `json`.`cert_user_id` , `json`.`user_name` , `json`.`site` , `json`.`directory`
   FROM `post`
   LEFT JOIN `json`
   ON `post`.`json_id`=`json`.`json_id`
   ORDER BY `post_id` DESC
   LIMIT 500;
EOF;
   $ret = $db->query($sql);

$date_now = date(DATE_RSS);

$pre_loop="<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<rss xmlns:atom=\"{$PROTOCOL}://www.w3.org/2005/Atom\" version=\"2.0\">
  <channel>
    <atom:link href=\"{$RSS_URI}\" rel=\"self\" type=\"application/rss+xml\"/>
    <title>ZeroMePlus</title>
    <link>{$PROTOCOL}://{$ZERONET_HOST}/{$ZEROME_URI}/</link>
    <image>
      <title>ZeroMePlus</title>
      <url>{$PROTOCOL}://{$ZERONET_HOST}/{$ZEROME_URI}/img/logo.png</url>
      <link>{$PROTOCOL}://{$ZERONET_HOST}/{$ZEROME_URI}/</link>
    </image>
    <description>Raw feed from ZeroMePlus,Github: https://github.com/MilitaryRiotLab/ZeroMe-RSS</description>
    <lastBuildDate>$date_now</lastBuildDate>
    <ttl>1</ttl>
";

	while($row = $ret->fetchArray(SQLITE3_ASSOC) ){
	$user_name = htmlspecialchars($row['user_name'], ENT_XML1, 'UTF-8');
	$cert_user_id = htmlspecialchars($row['cert_user_id'], ENT_XML1, 'UTF-8');
	$site = htmlspecialchars($row['site'], ENT_XML1, 'UTF-8');
	$post_id = htmlspecialchars($row['post_id'], ENT_XML1, 'UTF-8');
	$body = htmlspecialchars($row['body'], ENT_XML1, 'UTF-8');
	$directory = str_replace('data/users/','',$row['directory']);
	$date = date(DATE_RSS,$row['date_added']);

$loop.="
	<item>
      <title>{$user_name} ({$cert_user_id})</title>
      <link>{$PROTOCOL}://{$ZERONET_HOST}/{$ZEROME_URI}/?Post/{$site}/{$directory}/{$post_id}</link>
      <description>{$body}</description>
      <pubDate>{$date}</pubDate>
      <guid>{$PROTOCOL}://{$ZERONET_HOST}/{$ZEROME_URI}/?Post/{$site}/{$directory}/{$post_id}</guid>
	</item>
";


	}

$post_loop=<<<EOF
	</channel>
</rss>
EOF;

$output = $pre_loop.$loop.$post_loop;

$xmlfile = fopen($XML_OUTPUT, "w") or die("Unable to open file!");
fwrite($xmlfile, $output);
fclose($xmlfile);

$output_gzip = gzencode($output,9);

$xmlfile_gzip = fopen($XML_OUTPUT.'.gz', "w") or die("Unable to open gzip file!");
fwrite($xmlfile_gzip, $output_gzip);
fclose($xmlfile_gzip);


echo 'Jobs done';

$db->close();
   // Reference sqlite
   // http://www.tutorialspoint.com/sqlite/sqlite_php.htm
?>
