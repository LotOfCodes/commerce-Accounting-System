<?php
error_reporting(0);
date_default_timezone_set('Asia/Shanghai');

$picUrl = isset($_GET['picUrl']) ? trim($_GET['picUrl']) : '';
$cacheDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'pic';
$maxBytes = 5 * 1024 * 1024;
$maxPixels = 16000000;
$blankFileName = 'blank.jpg';

if ($picUrl === '') {
	redirectToBlankImage();
}

if (!isValidRemoteUrl($picUrl)) {
	redirectToBlankImage();
}

$fileName = md5($picUrl) . '.jpg';
$filePath = $cacheDir . DIRECTORY_SEPARATOR . $fileName;

if (!is_dir($cacheDir) && !mkdir($cacheDir, 0755, true) && !is_dir($cacheDir)) {
	serveBlankImage();
}

if (is_file($filePath) && filesize($filePath) > 0) {
	if (isSafeCachedImage($filePath)) {
		serveImage($filePath);
	}
	@unlink($filePath);
}

$imageData = downloadImage($picUrl, $maxBytes);
$imageData = normalizeToJpeg($imageData, $maxPixels);
saveImage($filePath, $imageData);
redirectToCachedImage($fileName);

function isValidRemoteUrl($url)
{
	$parts = parse_url($url);
	if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
		return false;
	}

	$scheme = strtolower($parts['scheme']);
	if ($scheme !== 'http' && $scheme !== 'https') {
		return false;
	}

	$host = strtolower($parts['host']);
	if (!isSafeRemoteHost($host)) {
		return false;
	}

	return true;
}

function isSafeRemoteHost($host)
{
	if ($host === '' || strlen($host) > 253) {
		return false;
	}

	if (preg_match('/[\x00-\x20\/\\\\]/', $host)) {
		return false;
	}

	$host = trim($host, '[]');
	if ($host === 'localhost' || substr($host, -10) === '.localhost') {
		return false;
	}

	$records = @dns_get_record($host, DNS_A + DNS_AAAA);
	if (!$records) {
		$ip = @gethostbyname($host);
		$records = $ip && $ip !== $host ? array(array('ip' => $ip)) : array();
	}

	if (!$records) {
		return false;
	}

	foreach ($records as $record) {
		$ip = isset($record['ip']) ? $record['ip'] : (isset($record['ipv6']) ? $record['ipv6'] : '');
		if ($ip === '' || !isPublicIp($ip)) {
			return false;
		}
	}

	return true;
}

function isPublicIp($ip)
{
	return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

function downloadImage($url, $maxBytes)
{
	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_MAXREDIRS => 3,
			CURLOPT_CONNECTTIMEOUT => 6,
			CURLOPT_TIMEOUT => 20,
			CURLOPT_MAXFILESIZE => $maxBytes,
			CURLOPT_USERAGENT => 'FacShipImageLoader/1.0',
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
		));

		$data = curl_exec($ch);
		$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$contentType = strtolower((string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
		$effectiveUrl = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		curl_close($ch);

		if ($data === false || $httpCode < 200 || $httpCode >= 300) {
			redirectToBlankImage();
		}
		if (!isValidRemoteUrl($effectiveUrl) || !isAllowedImageContentType($contentType)) {
			redirectToBlankImage();
		}
	} else {
		$context = stream_context_create(array(
			'http' => array(
				'timeout' => 20,
				'follow_location' => 0,
				'max_redirects' => 3,
				'header' => "User-Agent: FacShipImageLoader/1.0\r\n",
			),
			'https' => array(
				'timeout' => 20,
				'follow_location' => 0,
				'max_redirects' => 3,
				'header' => "User-Agent: FacShipImageLoader/1.0\r\n",
			),
		));
		$data = @file_get_contents($url, false, $context);

		if ($data === false) {
			redirectToBlankImage();
		}
	}

	if (strlen($data) === 0 || strlen($data) > $maxBytes) {
		redirectToBlankImage();
	}

	if (@getimagesizefromstring($data) === false) {
		redirectToBlankImage();
	}

	return $data;
}

function isAllowedImageContentType($contentType)
{
	if ($contentType === '') {
		return true;
	}
	return strpos($contentType, 'image/') === 0;
}

function normalizeToJpeg($data, $maxPixels)
{
	$info = @getimagesizefromstring($data);
	if (!$info) {
		redirectToBlankImage();
	}

	$width = isset($info[0]) ? (int)$info[0] : 0;
	$height = isset($info[1]) ? (int)$info[1] : 0;
	if ($width <= 0 || $height <= 0 || ($width * $height) > $maxPixels) {
		redirectToBlankImage();
	}

	if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
		redirectToBlankImage();
	}

	$image = @imagecreatefromstring($data);
	if (!$image) {
		redirectToBlankImage();
	}

	if (function_exists('imagepalettetotruecolor')) {
		@imagepalettetotruecolor($image);
	}

	ob_start();
	imagejpeg($image, null, 90);
	$jpegData = ob_get_clean();
	imagedestroy($image);

	if ($jpegData === false || strlen($jpegData) === 0) {
		redirectToBlankImage();
	}

	return $jpegData;
}

function isSafeCachedImage($filePath)
{
	$info = @getimagesize($filePath);
	return $info && isset($info[2]) && (int)$info[2] === IMAGETYPE_JPEG;
}

function saveImage($filePath, $data)
{
	$tmpPath = $filePath . '.tmp.' . getmypid();
	if (file_put_contents($tmpPath, $data, LOCK_EX) === false) {
		redirectToBlankImage();
	}

	if (!rename($tmpPath, $filePath)) {
		@unlink($tmpPath);
		redirectToBlankImage();
	}
}

function serveImage($filePath)
{
	header('Content-Type: image/jpeg');
	header('Content-Length: ' . filesize($filePath));
	header('Cache-Control: public, max-age=31536000, immutable');
	readfile($filePath);
	exit;
}

function redirectToCachedImage($fileName)
{
	header('Location: ' . publicImageUrl($fileName), true, 302);
	exit;
}

function redirectToBlankImage()
{
	ensureBlankImage();
	header('Location: ' . publicImageUrl($GLOBALS['blankFileName']), true, 302);
	exit;
}

function publicImageUrl($fileName)
{
	$host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/[^A-Za-z0-9.:-]/', '', $_SERVER['HTTP_HOST']) : '';
	if ($host === '') {
		$host = 'localhost';
	}

	return 'https://' . $host . '/pic/' . rawurlencode($fileName);
}

function ensureBlankImage()
{
	$cacheDir = $GLOBALS['cacheDir'];
	$blankFileName = $GLOBALS['blankFileName'];
	$blankPath = $cacheDir . DIRECTORY_SEPARATOR . $blankFileName;

	if (is_file($blankPath) && filesize($blankPath) > 0) {
		return;
	}

	if (!is_dir($cacheDir) && !mkdir($cacheDir, 0755, true) && !is_dir($cacheDir)) {
		serveBlankImage();
	}

	$data = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAH/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAEFAqf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/ASP/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/ASP/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAY/Al//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/IV//2gAMAwEAAgADAAAAEP/EABQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQMBAT8QP//EABQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQIBAT8QP//EABQQAQAAAAAAAAAAAAAAAAAAABD/2gAIAQEAAT8QP//Z');
	if (file_put_contents($blankPath, $data, LOCK_EX) === false) {
		serveBlankImage();
	}
}

function serveBlankImage()
{
	$data = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAH/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAEFAqf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/ASP/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/ASP/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAY/Al//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/IV//2gAMAwEAAgADAAAAEP/EABQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQMBAT8QP//EABQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQIBAT8QP//EABQQAQAAAAAAAAAAAAAAAAAAABD/2gAIAQEAAT8QP//Z');
	header('Content-Type: image/jpeg');
	header('Cache-Control: public, max-age=31536000, immutable');
	echo $data;
	exit;
}
?>
