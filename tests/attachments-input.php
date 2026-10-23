<?php
/**
 * Contract of the shared form attachments (AttachmentsInputWidget): compact
 * attaching as in the letter form, files kept in the temporary area until
 * the record is saved, the CRM comment form built on it.
 * No site bootstrap or DB. Usage: php tests/attachments-input.php
 */

function attachmentsExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException('Failed attachments contract: '.$message);
    }
}

$src = dirname(__DIR__).'/src';
$widget = file_get_contents($src.'/widgets/formInputs/attachments/AttachmentsInputWidget.php');
$js = file_get_contents($src.'/widgets/formInputs/attachments/assets/attachments.js');
$css = file_get_contents($src.'/widgets/formInputs/attachments/assets/attachments.css');
$controller = file_get_contents($src.'/controllers/TemporaryUploadController.php');
$behavior = file_get_contents($src.'/models/behaviors/HasStorageFileMulti.php');
$comment = file_get_contents($src.'/widgets/admin/views/comment.php');

// Uploads go to the session-bound temporary area, never straight into the storage.
attachmentsExpect(strpos($controller, "TemporaryUploads::put(\$file, 'att')") !== false, 'the endpoint stores into the temporary area');
attachmentsExpect(strpos($controller, "'roles' => ['@']") !== false, 'the endpoint is for signed-in users only');
attachmentsExpect(strpos($controller, "'attach' => ['POST']") !== false, 'the endpoint accepts POST only');
attachmentsExpect(strpos($controller, 'storage->upload') === false, 'the endpoint does not write to the storage');

// The model side: tokens of this session are stored on save, stale ones are a field error.
attachmentsExpect(substr_count($behavior, 'TemporaryUploads::isToken($fileId)') >= 3, 'HasStorageFileMulti recognizes tokens in fields, relations and validation');
attachmentsExpect(strpos($behavior, 'TemporaryUploads::store($token') !== false, 'tokens are moved into the storage on save');
attachmentsExpect(strpos($behavior, 'EVENT_BEFORE_VALIDATE') !== false && strpos($behavior, 'устарел') !== false, 'a stale token is a validation error');

// The widget: chips with size and ×, the button rendered by the host form, drop and paste.
attachmentsExpect(strpos($widget, 'public static function button(') !== false, 'the host form places the button itself');
attachmentsExpect(strpos($widget, "'data-sx-attach-remove' => true") !== false, 'every chip can be removed');
attachmentsExpect(strpos($widget, 'asShortSize($size)') !== false, 'chips show the file size');
attachmentsExpect(strpos($widget, 'public $listContent') !== false, 'host forms can add their own chips (letter "Прикрепить из…")');
attachmentsExpect(strpos($js, "on('drop'") !== false && strpos($js, "on('paste'") !== false, 'files are attached by drop and paste');
attachmentsExpect(strpos($js, "'sx-rte:attach'") !== false, 'files handed over by the rich-text editor are attached');
attachmentsExpect(strpos($js, 'stopImmediatePropagation') !== false, 'a form is not sent while files upload');
attachmentsExpect(strpos($js, "trigger('sx-attach:change')") !== false, 'host forms learn about changes');
attachmentsExpect(strpos($css, 'var(--sx-color-') !== false && strpos($css, '#') !== false, 'colors come from the theme variables with fallbacks');
attachmentsExpect(strpos($css, 'col-') === false && strpos($widget, 'btn ') === false, 'no Bootstrap in the shared markup');

// The CRM comment form uses it: send and attach in one row, files given by the editor attached.
attachmentsExpect(strpos($comment, 'AjaxFileUploadWidget') === false, 'the comment form no longer uses the bulky upload widget');
attachmentsExpect(strpos($comment, "AttachmentsInputWidget::widget([") !== false, 'the comment form uses the shared attachments');
attachmentsExpect(strpos($comment, "'attribute' => 'fileIds'") !== false, 'comment files stay in CmsLog::fileIds');
attachmentsExpect(strpos($comment, "'attachLabel' => \$widget->isShowAttachments ? 'Прикрепить файлом' : false") !== false, 'the comment editor hands over screenshots and files');
$actions = substr($comment, strpos($comment, '<div class="sx-comment-actions">'));
attachmentsExpect(strpos($actions, 'type="submit"') < strpos($actions, 'AttachmentsInputWidget::button()'), 'the attach button follows the send button in the actions row');

echo "Attachments input contract: OK\n";
