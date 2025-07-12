<?php
session_start();
require_once '../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Akses ditolak. Silakan login terlebih dahulu.']);
    exit();
}

$objectKey = trim($_GET['key'] ?? '');
if (empty($objectKey)) {
    echo json_encode(['success' => false, 'error' => 'Path file tidak valid.']);
    exit();
}

$s3Config = require '../config/s3.php';
$bucketName = $s3Config['bucket'];

try {
    $s3Client = new S3Client([
        'credentials' => $s3Config['credentials'],
        'region'      => $s3Config['region'],
        'version'     => $s3Config['version'],
        'endpoint'    => $s3Config['endpoint'],
    ]);

    $command = $s3Client->getCommand('GetObject', [
        'Bucket' => $bucketName,
        'Key'    => $objectKey,
    ]);

    $presignedRequest = $s3Client->createPresignedRequest($command, '+10 minutes');

    $presignedUrl = (string) $presignedRequest->getUri();

    echo json_encode(['success' => true, 'url' => $presignedUrl]);

} catch (S3Exception $e) {
    echo json_encode(['success' => false, 'error' => 'File tidak dapat diakses: ' . $e->getAwsErrorCode()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
}