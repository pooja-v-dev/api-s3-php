<?php
if(!isset($_POST['s3bucket']) || empty($_POST['s3bucket'])){
    echo "Please enter the s3bucket";
    exit;
}


if(!isset($_FILES['filename']) || empty($_FILES['filename'])){
    echo "Please select file";
    exit;
}

$bucketName = $_POST['s3bucket'];
$file = $_FILES['filename'];

// echo $bucketName;
// print_r($file);

require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

$IAM_KEY = '';
$IAM_SECRET = '';

$name = $_FILES['filename']["name"];
$tmpName = $_FILES['filename']["tmp_name"];
$type = $_FILES['filename']["type"];
$size = $_FILES['filename']["size"];
$errorMsg = $_FILES['filename']["error"];
$explode = explode(".",$name);
$extension = end($explode);

if(!$tmpName)
{
    echo "ERROR: Please choose file";
    exit();
}
else if($size > 5242880)
{
    echo "ERROR: Please choose less than 5MB file for uploading";
    unlink($tmpName);
    exit();
}
else if(!preg_match("/\.(jpg|png|jpeg)$/i",$name)) 
{
    echo "ERROR: Please choose the file only with the JPEG file format";
    unlink($tmpName);
    exit();
}
else if($errorMsg == 1)
{
    echo "ERROR: An unexpected error occured while processing the file. Please try again.";
    exit();
}

$uploaddir = __DIR__.'/uploads/';
$uploadfile = $uploaddir . basename($_FILES['filename']['name']);

if (!file_exists($uploaddir)) {
    mkdir($uploaddir, 0777, true);
}

$moveFile = move_uploaded_file($tmpName,$uploadfile);

if($moveFile != true)
{
    echo "ERROR: File not uploaded. Please try again";
    unlink($tmpName);
    exit();
}


// include_once("upld_fn.php");
$target = "uploads/$name";

$img_path = "uploads/".$name;

try {

    $s3 = S3Client::factory(
        array(
            'credentials' => array(
                'key' => $IAM_KEY,
                'secret' => $IAM_SECRET
            ),
            'version' => 'latest',
            'region'  => 'ap-south-1'
        )
    );
} catch (Exception $e) {

    die("Error: " . $e->getMessage());
}


$keyName = 'test_example/' . basename($_FILES['filename']['name']);
$pathInS3 = 'https://s3.ap-south-1.amazonaws.com/' . $bucketName . '/' . $keyName;

global $url;
try {

    $file = $_FILES['filename']['tmp_name'];

    $result = $s3->putObject(
        array(
            'Bucket' => $bucketName,
            'Key' =>  $keyName,
            'SourceFile' => $img_path,
            'StorageClass' => 'REDUCED_REDUNDANCY',
            'ACL' => 'public-read'
        )
    );

    $url = $result->get('ObjectURL');
    // echo "<br>Image uploaded successfully. Image path is: ". $result->get('ObjectURL');
    $data = ['success'=> $_FILES['filename']['name']];
    echo json_encode($data);

} catch (S3Exception $e) {
    die('Error:' . $e->getMessage());
} catch (Exception $e) {
    die('Error:' . $e->getMessage());
}


