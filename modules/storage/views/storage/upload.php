<?php
/**
 * @var \yii\web\View $this
 */
?>
<div class="modal__container">
    <div class="modal__header">
        <div class="modal__heading">File upload <span class="hidden">done</span></div>
    </div>
    <div class="modal__body">
        <div>Uploading file <span data-file-index="1">{index}</span> of <span data-file-total>{total}</span>:</div>
        <div class="progress-bar progress-bar_light" data-file="{file}">
            <div class="progress-bar__ribbon"></div>
        </div>
        <ul class="upload-errors"></ul>
    </div>
    <div class="modal__body modal__body_done hidden">
        <p class="text_center">All selected files uploaded!</p>
    </div>
    <div class="modal__footer text_center">
        <button type="button" class="btn btn_default" data-dismiss="modal">Close</button>
    </div>
</div>