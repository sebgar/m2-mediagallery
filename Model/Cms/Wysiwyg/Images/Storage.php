<?php

namespace Sga\MediaGallery\Model\Cms\Wysiwyg\Images;

class Storage extends \Magento\Cms\Model\Wysiwyg\Images\Storage
{
    public function uploadFile($targetPath, $type = null)
    {
        if (!$this->isPathAllowed($targetPath, $this->getConditionsForExcludeDirs())) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We can\'t upload the file to current folder right now. Please try another folder.')
            );
        }

        /** @var \Magento\MediaStorage\Model\File\Uploader $uploader */
        $uploader = $this->_uploaderFactory->create(['fileId' => 'image']);
        $allowed = $this->getAllowedExtensions($type);
        if ($allowed) {
            $uploader->setAllowedExtensions($allowed);
        }
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(false);
        if (!$uploader->checkMimeType($this->getAllowedMimeTypes($type))) {
            throw new \Magento\Framework\Exception\LocalizedException(__('File validation failed.'));
        }
        $result = $uploader->save($targetPath);

        if (!$result) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t upload the file right now.'));
        }

        ///// BEGIN SGA
        if (strtolower($uploader->getFileExtension()) !== 'pdf') {
        ///// END SGA
            // Create Thumbnail
            $this->resizeFile($targetPath . '/' . $uploader->getUploadedFileName(), true);
        ///// BEGIN SGA
        }
        ///// END SGA

        return $result;
    }

    /**
     * Prepare mime types config settings.
     *
     * @param string|null $type Type of storage, e.g. image, media etc.
     * @return array Array of allowed file extensions
     */
    private function getAllowedMimeTypes($type = null): array
    {
        $allowed = $this->getExtensionsList($type);

        return array_values(array_filter($allowed));
    }

    /**
     * Get list of allowed file extensions with mime type in values.
     *
     * @param string|null $type
     * @return array
     */
    private function getExtensionsList($type = null): array
    {
        if (is_string($type) && array_key_exists("{$type}_allowed", $this->_extensions)) {
            $allowed = $this->_extensions["{$type}_allowed"];
        } else {
            $allowed = $this->_extensions['allowed'];
        }

        return $allowed;
    }

    /**
     * Check if path is not in excluded dirs.
     *
     * @param string $path Absolute path
     * @param array $conditions Exclude conditions
     * @return bool
     */
    private function isPathAllowed($path, array $conditions): bool
    {
        $isAllowed = true;
        $regExp = $conditions['reg_exp'] ? '~' . implode('|', array_keys($conditions['reg_exp'])) . '~i' : null;
        $storageRoot = $this->_cmsWysiwygImages->getStorageRoot();
        $storageRootLength = strlen($storageRoot);

        $mediaSubPathname = substr($path, $storageRootLength);
        $rootChildParts = explode('/', '/' . ltrim($mediaSubPathname, '/'));

        if (array_key_exists($rootChildParts[1], $conditions['plain'])
            || ($regExp && preg_match($regExp, $path))) {
            $isAllowed = false;
        }

        return $isAllowed;
    }
}
