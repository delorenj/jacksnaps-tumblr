<?php
require_once 'config/config.inc';

class Jacksnaps {
  private static $instance;
  private $conn;
  private $tumblr_email;
  private $tumblr_password;
  private $post_type;
  private $post_generator;

  public static function instance() {
    if(!isset(self::$instance)) {
      $c = __CLASS__;
      self::$instance = new $c;
    }
    return self::$instance;
  }

  private function  __construct() {
    $dbdata = array("server" => SQL_HOST,
     "username" => SQL_USER,
     "password" => SQL_PASSWORD,
     "dbname" => SQL_DATABASE
    );

    $conn = mysql_connect($dbdata["server"], $dbdata["username"], $dbdata["password"])
    or die("Could not connect:". mysql_error());

    mysql_select_db($dbdata["dbname"])
    or die("Could not select database:". mysql_error());

    // Authorization info
    $this->tumblr_email = TUMBLR_EMAIL;
    $this->tumblr_password = TUMBLR_PASSWORD;

    //Default parameters
    $this->post_type = 'photo';
    $this->post_generator = 'Jacksnaps Food and Tractor Generator Machine';


  } //End Constructor

  public function __clone() {
    trigger_error("Can't clone singleton class: Jacksnaps");
  }

  public function count() {
    $result = mysql_query("SELECT jacksnaps_id FROM jacksnaps");
    return mysql_num_rows($result);
  }

  public function fetch_jacksnap($rating = 3) {
    $result = mysql_query("SELECT jacksnaps_text,jacksnaps_image FROM jacksnaps where jacksnaps_rating > ". $rating . " AND jacksnaps_image IS NULL ORDER BY RAND() LIMIT 1");
    if(mysql_num_rows($result) == 0) {
      $this->emailNotice("Jacksnaps Maintenance Alert!", "No Jacksnaps Left with rating greater than " , $rating . "!");
    }
    $row = mysql_fetch_row($result);
    return $row[0];
  }

  public function fetch_image() {
    $files = array();
    $dir = opendir('./images');
    while ($file = readdir($dir)) {
      if ($file != '.' && $file != '..' && !is_dir('./images/'.$file)) {
        array_push($files, $file);
      }
    }
    closedir($dir);
    if(count($files) < 5) {
      $this->emailNotice('Jacksnaps Maintenance Notice!', count($files).' pictures remain in your current collection. Get more!');
    }
    if(count($files) == 0) {
      die("\nNo images left");
    }
    srand(time());
    $num = rand() % count($files);
    return $files[$num];
  }

  public function emailNotice($subject, $body) {
    echo("SENDING ALERT EMAIL (todo)");
    echo("Subject: " . $subject);
    echo("Body: " . $body);
  }

  private function mark_as_used($text, $image) {
    $result = mysql_query("update jacksnaps set jacksnaps_image=\"$image\" where jacksnaps_text LIKE \"$text\"");
    rename('./images/'.$image, './images/used/'.$image);
  }

  public function post_caption($text, $image) {
  // Prepare POST request
    $post_data = dirname(__FILE__) . '/images/' . $image;
    $request_data = array(
        'email' => $this->tumblr_email,
        'password' => $this->tumblr_password,
        'type' => $this->post_type,
        'data' => '@' . $post_data,
        'caption' => $text,
        'generator' => $this->post_generator
    );
//      $this->mark_as_used($text, $image);
    // Send the POST request (with cURL)
    $c = curl_init('http://www.tumblr.com/api/write');
    curl_setopt($c, CURLOPT_POST, true);
    curl_setopt($c, CURLOPT_POSTFIELDS, $request_data);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($c);
    $status = curl_getinfo($c, CURLINFO_HTTP_CODE);
    curl_close($c);

    // Check for success
    if ($status == 201) {
      echo "Success! The new post ID is $result.\n";
      $this->mark_as_used($text, $image);
    } else if ($status == 403) {
      echo 'Bad email or password';
    } else {
      echo "Error: $result\n";
      echo "Image Path: " . $post_data;
    }
  }
}

?>
