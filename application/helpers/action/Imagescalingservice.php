<?php

class Helper_Action_Imagescalingservice extends Zend_Controller_Action_Helper_Abstract {

    private $cropAndResizeImageHelper;
    private $docRootHelper;

    public function init() {
        $this->cropAndResizeImageHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Cropandresizeimage');
        $this->docRootHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Docroothelper');
    }

    public function createSizedImages($source, $newsource, $imagename) {
        $image = $this->cropAndResizeImageHelper->setparams($source, $source);
        $image_saved = $this->docRootHelper->saveFile($source, $newsource . 'original/' . $imagename . '.jpg');
        
        $image = $this->cropAndResizeImageHelper->setparams($source, $source, null, null, '600');
        $image_saved = $this->docRootHelper->saveFile($source, $newsource . 'large/' . $imagename . '.jpg');

        $image = $this->cropAndResizeImageHelper->setparams($source, $source, '1:1', '190x');
        $image_saved = $this->docRootHelper->saveFile($source, $newsource . 'medium/' . $imagename . '.jpg');

        $image = $this->cropAndResizeImageHelper->setparams($source, $source, '1:1', '75x');
        $image_saved = $this->docRootHelper->saveFile($source, $newsource . 'small/' . $imagename . '.jpg');
        return $image_saved;
    }

}

?>