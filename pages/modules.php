<?php

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * hide modules if redaxo version < 5.12.0 -> symfony/http-foundation
 * https://github.com/redaxo/redaxo/releases/tag/5.12.0.
 */
if (rex_version::compare(rex::getVersion(), '5.12.0', '<')) {
    $warning = '<div class="alert alert-warning" style="margin-bottom: 0" role="alert"><strong>' . $this->i18n('version_warning') . '</strong></div>';
    $fragment = new rex_fragment();
    $fragment->setVar('body', $warning, false);
    $content = $fragment->parse('core/page/section.php');
    echo $content;
} else {
    /** @var rex_addon $this */
    $maxFilesToUpload = (int) ini_get('max_file_uploads');

    if (!empty(rex_post('module_upload'))) {
        $targetDir = rex_url::assets('addons/module_preview_modules/');
        $module = rex_sql::factory();
        $moduleIds = $module->getArray('select id from ' . rex::getTablePrefix() . 'module order by name');
        $maxFileSize = (int) ini_get('upload_max_filesize');
        $imageCount = 1;
        $error = false;

        foreach ($moduleIds as $moduleId) {
            $tmpImage = rex_files('module_' . $moduleId['id']);

            if (!$tmpImage || (!$tmpImage['tmp_name'] && 0 !== $tmpImage['error'])) {
                continue;
            }

            if ($imageCount > $maxFilesToUpload) {
                echo rex_view::error($this->i18n('module_preview_upload_max_file_uploads', $maxFilesToUpload));
                $error = true;
                break;
            }

            if ($tmpImage['size'] > UploadedFile::getMaxFilesize()) {
                echo rex_view::error($this->i18n('module_preview_upload_max_filesize'));
                continue;
            }

            $module = rex_sql::factory();
            $module = $module->getArray('select * from ' . rex::getTable('module') . ' WHERE id = :id LIMIT 1', ['id' => $moduleId['id']]);
            $fileName = $moduleId['id'];

            if (array_key_exists('key', $module[0]) && isset($module[0]['key'])) {
                $fileName = $module[0]['key'];
            }

            $uploadedImage = new UploadedFile($tmpImage['tmp_name'], $tmpImage['name'], $tmpImage['type'], $tmpImage['error']);
            $uploadedImage->move($targetDir, $fileName . '.jpg');
            ++$imageCount;
        }

        if (!$error) {
            echo rex_view::success($this->i18n('saved'));
        }
    }

    if (!empty(rex_post('delete_image'))) {
        ob_end_clean();
        $image = rex_post('image');
        if (rex_post('image') && file_exists($image)) {
            if (rex_file::delete($image)) {
                http_response_code(200);
            }
        } else {
            echo $this->i18n('module_preview_image_not_found');
            http_response_code(404);
        }
        exit;
    }

    $maxFilesToUpload = (int) ini_get('max_file_uploads');

    $content = '<fieldset>';

    $formElements = [];

    $module = rex_sql::factory();
    $modules = $module->getArray('select * from ' . rex::getTablePrefix() . 'module order by name');

    $content .= '<div class="container-fluid module-container">';
    $content .= '<p class="help-block rex-note">' . $this->i18n('module_preview_upload_max_file_uploads', $maxFilesToUpload) . '</p>';
    $content .= '<form action="' . rex_url::currentBackendPage() . '" method="POST" enctype="multipart/form-data" class="module-row">';
    foreach ($modules as $module) {
        $fragment = new rex_fragment();
        $fragment->setVar('module', $module);
        $content .= $fragment->parse('module_preview/image-upload.php');
    }
    $content .= '<input type="hidden" name="random" id="random" value="' . microtime() . '" />';
    $content .= '<div class="col-sm-12"><input type="submit" name="module_upload" value="' . $this->i18n('module_preview_save') . '" class="btn btn-save"></div>';
    $content .= '</form>';
    $content .= '</div>';

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit');
    $fragment->setVar('title', $this->i18n('settings'));
    $fragment->setVar('body', $content, false);
    $content = $fragment->parse('core/page/section.php');

    echo $content;
}
?>

<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>
