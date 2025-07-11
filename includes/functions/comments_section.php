
// For each comment:

<div class="comment-header">
    <?= getUserProfilePhotoWithName($comment['user_id'], $comment['user_name']) ?>
    <small class="text-muted ms-2"><?= time_elapsed_string($comment['created_at']) ?></small>
</div>
