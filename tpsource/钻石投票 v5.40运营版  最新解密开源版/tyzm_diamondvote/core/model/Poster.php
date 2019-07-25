<?php


	exit('Access Denied');
} 
class Tyzm_Poster{

	public function __construct() {
		global $_W;
	}
	public function createPoster($fans, $posterConfig)
    {
        global $_W;

        $uid = $fans['uid'];

        $posterBgImage = tomedia($posterConfig['img']);

        if (empty($posterBgImage)) {
            return null;
        }

        $posterData = $posterConfig['data'];

        if (extension_loaded('imagick')) {
            $posterImage = new Imagick(ATTACHMENT_ROOT . $posterBgImage);
            $posterImageSize = $posterImage->getImageGeometry();
            $posterImageWidth = $posterImageSize['width'];
            $posterDesignImageWidth = 320;
            $posterImageScale = $posterImageWidth / $posterDesignImageWidth;

            if (!empty($posterData)) {
                require_once(MODULE_ROOT . '/lib/qrcode/phpqrcode.php');
                load()->func('file');
                load()->func('communication');
                foreach ($posterData as $data) {
                    switch ($data['type']) {
                        case 'avatar':
                            if (!empty($fans['avatar'])) {
                                $avatarFilePath = ATTACHMENT_ROOT . 'zp_jifen/avatar/' . $_W['uniacid'] . '/' . $uid . '.jpg';
                                $avatarFilePathStatus = file_exists(dirname($avatarFilePath));
                                if (!$avatarFilePathStatus) {
                                    $avatarFilePathStatus = mkdirs(dirname($avatarFilePath));
                                }
                                if ($avatarFilePathStatus) {
                                    $resp = ihttp_get($fans['avatar']);
                                    if (!is_error($resp) && intval($resp['code']) == 200) {
                                        if (file_put_contents($avatarFilePath, $resp['content'])) {
                                            $avatarImage = new Imagick($avatarFilePath);
                                            $avatarImage->thumbnailImage(substr($data['width'], 0, -2) * $posterImageScale, substr($data['height'], 0, -2) * $posterImageScale, true, true);
                                            $posterImage->compositeImage($avatarImage, Imagick::COMPOSITE_OVER, substr($data['left'], 0, -2) * $posterImageScale, substr($data['top'], 0, -2) * $posterImageScale);
                                        }
                                    }
                                }
                            }
                            break;
                        case 'name':
                            if (!empty($fans['nickname'])) {
                                $draw = new ImagickDraw;
                                $draw->setFont(MODULE_ROOT . '/lib/font/msyh.ttf');
                                $draw->setFontSize(intval(substr($data['size'], 0, -2)) * $posterImageScale);
                                $draw->setFillColor(new ImagickPixel($data['color']));
                                $posterImage->annotateImage($draw, intval(substr($data['left'], 0, -2)) * $posterImageScale, intval(substr($data['top'], 0, -2)) * $posterImageScale, 0, $fans['nickname']);
                            }
                            break;
                        case 'qr':
                            if ($_W['account']['level'] == 4) {
                                $qrCodeData = $this->createQRCode($uid);
                                if (!empty($qrCodeData)) {
                                    $qrCodeFilePath = ATTACHMENT_ROOT . 'zp_jifen/qrcode/' . $_W['uniacid'] . '/' . $uid . '.png';
                                    $qrCodeFilePathStatus = file_exists(dirname($qrCodeFilePath));
                                    if (!$qrCodeFilePathStatus) {
                                        $qrCodeFilePathStatus = mkdirs(dirname($qrCodeFilePath));
                                    }
                                    if ($qrCodeFilePathStatus && file_put_contents($qrCodeFilePath, $qrCodeData)) {
                                        $qrCodeImage = new Imagick($qrCodeFilePath);
                                        $qrCodeImage->thumbnailImage(substr($data['width'], 0, -2) * $posterImageScale, substr($data['height'], 0, -2) * $posterImageScale, true, true);
                                        $posterImage->compositeImage($qrCodeImage, Imagick::COMPOSITE_OVER, substr($data['left'], 0, -2) * $posterImageScale, substr($data['top'], 0, -2) * $posterImageScale);
                                    }
                                }
                                break;
                            }
                    }
                }
            }

            $posterFilePath = 'zp_jifen/poster/' . $_W['uniacid'] . '/' . $uid . '.jpg';
            $posterFileFullPath = ATTACHMENT_ROOT . $posterFilePath;
            $posterFilePathStatus = file_exists(dirname($posterFileFullPath));
            if (!$posterFilePathStatus) {
                $posterFilePathStatus = mkdirs(dirname($posterFileFullPath));
            }
            if (!$posterFilePathStatus) {
                return null;
            }
            $posterImage->setImageFormat('JPEG');
            $posterImage->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $posterImage->setImageCompressionQuality(90);
            $posterImage->stripImage();
            $posterImage->writeImage($posterFileFullPath);
            return $posterFileFullPath;
        }

        return null;

    }


 }