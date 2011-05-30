<?php
include '../app/config/bootstrap.php';
include '../core/convenience.php';
include '../core/orm/curl.php';

$base = str_replace('web/index.php', '', $_SERVER['SCRIPT_NAME']);

$query = (isset($_REQUEST['q'])) ?  $_REQUEST['q'] : '*';
$response = Curl::get(sprintf(
  "http://%s:%d/chatlogs/messages/_search?q=%s",
  $config['elastic_host'],
  $config['elastic_port'],
  str_replace(' ', '+', $query)
));

$response = json_decode($response->body)->hits;
$max_score = $response->max_score;
$messages = $response->hits;

$showing = count($response->hits);
$total = $response->total;

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
  <head>
    <title>Elastic Adium</title>
    <link href="<?php echo $base; ?>styles.css" media="screen" rel="stylesheet" type="text/css">
    <script src="<?php echo $base; ?>/jquery.js"></script>
    <script src="<?php echo $base; ?>/timeago.js"></script>
    <script>
      $(function() {
        $('#tips').hide();
        $(".date").timeago();
        $('.message .body')
          .hover(function() { $(this).css({ cursor : 'pointer' });})
          .click(function() {$(this).parent().toggleClass('expanded'); return false;});
        $('#toggle-tips').click(function() { $('#tips').toggle('fast'); return false; });
      });
      </script>
  </head>
  <body>
    <div id="search-form">

      <h1>
        <a href="<?php echo $base; ?>">Search your Logs</a><br>
        <a id="toggle-tips" href="#">Toggle tips</a>
      </h1>

      <form action="<?php echo $base; ?>" method="get" accept-charset="utf-8">
        <input type="text" name="q" value="<?php echo $query; ?>">
        <input type="submit" value="Search">
      </form>

      <div id="tools">
        <p>
          <!-- <span class="dim">Sort by:</span> relevance <span class="dim">or</span>
          <a href="<?php echo $base; ?>?q=<?php echo $query; ?>&amp;s=time">time</a> -->
          <span class="dim">. Showing <?php echo $showing; ?> of <?php echo $total; ?> total results.</span>
        </p>
      </div>

      <div id="tips" style="display: none; ">
        <p class="tip"><a href="<?php echo $base; ?>?q=hi*">hi*</a><span>Messages beginning with "hi"</span></p>
        <p class="tip"><a href="<?php echo $base; ?>?q=sender:laura">sender:laura</a><span>Messages from laura</span></p>
        <p class="tip"><a href="<?php echo $base; ?>?q=alcohol OR linux^100">alcohol OR love^100</a><span>Messages about Alcohol or Love, with a boost for Love</span></p>
        <p class="tip"><a href="<?php echo $base; ?>?q=time:[2011-05-22 TO 2011-05-29]">time:[2011-05-22 TO 2011-05-29]</a><span>Messages from last week</span></p>
      </div>
    </div>

    <?php foreach ($messages as $message) : ?>
      <div class="message">
        <p class="from">
          ["<?php echo $message->_source->sender; ?>"]
        </p>
        <p class="date" title="<?php echo $message->_source->time; ?>"><?php echo $message->_source->time; ?></p>

        <p class="subject">
          <?php if (in_array($message->_source->element, array('event', 'status'))) echo $message->_source->type; ?>
        </p>

        <?php if (strlen($message->_source->message)) : ?>
          <div class="body" style="cursor: pointer; ">
            <div class="stripped"><?php echo $message->_source->message; ?></div>
            <?php if (isset($message->_source->html) && strlen($message->_source->html)) : ?>
              <div class="html" class="display:none"><?php echo $message->_source->html; ?></div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

      </div>
    <?php endforeach; ?>

  </body>
</html>