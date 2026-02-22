<?php
require_once __DIR__ . '/includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();
$userId = (int) $currentUser['id'];

$toId = isset($_GET['to']) ? (int) $_GET['to'] : 0;
$convId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($toId > 0 && $toId != $userId) {
    $convId = getOrCreatePrivateConversation($userId, $toId);
}

$conversations = getConversationsForUser($userId);
$currentConv = null;
$currentMessages = [];
$otherUser = null;

if ($convId > 0) {
    foreach ($conversations as $c) {
        if ((int) $c['id'] === $convId) {
            $currentConv = $c;
            $otherUser = $c['other'];
            break;
        }
    }
    if ($currentConv) {
        markConversationRead($convId, $userId);
        $currentMessages = getConversationMessages($convId, $userId, 1, 50);
    }
}

$pageTitle = '站内消息';
include __DIR__ . '/includes/header.php';
?>

<div class="messages-page">
    <div class="messages-layout">
        <aside class="messages-sidebar">
            <div class="messages-sidebar-header">
                <h3><i class="fas fa-comments"></i> 消息</h3>
            </div>
            <ul class="conversation-list">
                <?php foreach ($conversations as $c): ?>
                <li class="conversation-item <?php echo $currentConv && (int)$c['id'] === (int)$currentConv['id'] ? 'active' : ''; ?>">
                    <a href="messages.php?id=<?php echo $c['id']; ?>">
                        <img src="uploads/avatars/<?php echo $c['other']['avatar'] ?? 'default_avatar.png'; ?>" alt="" class="conv-avatar">
                        <div class="conv-info">
                            <span class="conv-name"><?php echo clean($c['other']['nickname'] ?? $c['other']['username'] ?? ''); ?></span>
                            <span class="conv-preview"><?php echo clean(truncateText($c['last_content'] ?? '', 30)); ?></span>
                        </div>
                        <?php if (!empty($c['unread'])): ?>
                        <span class="conv-unread"><?php echo $c['unread']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if (empty($conversations)): ?>
            <p class="text-muted" style="padding: 1rem;">暂无会话</p>
            <?php endif; ?>
        </aside>
        <div class="messages-main">
            <?php if ($currentConv && $otherUser): ?>
            <div class="messages-header">
                <a href="user_profile.php?id=<?php echo $otherUser['id']; ?>"><?php echo clean($otherUser['nickname'] ?? $otherUser['username']); ?></a>
            </div>
            <div class="messages-list" id="messagesList" data-conversation-id="<?php echo $convId; ?>">
                <?php foreach ($currentMessages as $m): ?>
                <div class="message-item <?php echo (int)$m['sender_id'] === $userId ? 'self' : ''; ?>" data-msg-id="<?php echo $m['id']; ?>">
                    <img src="uploads/avatars/<?php echo $m['avatar']; ?>" alt="" class="msg-avatar">
                    <div class="msg-body">
                        <span class="msg-author"><?php echo clean($m['nickname'] ?: $m['username']); ?></span>
                        <div class="msg-content"><?php echo nl2br(clean($m['content'])); ?></div>
                        <span class="msg-time"><?php echo timeAgo($m['created_at']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="messages-input">
                <form id="sendMessageForm">
                    <input type="hidden" name="conversation_id" value="<?php echo $convId; ?>">
                    <textarea name="content" id="messageContent" rows="2" placeholder="输入消息..." required></textarea>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> 发送</button>
                </form>
            </div>
            <?php else: ?>
            <div class="messages-empty">
                <i class="fas fa-comment-dots"></i>
                <p>选择左侧会话或从用户主页点击「发消息」开始聊天</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($currentConv): ?>
<script>
(function() {
    var list = document.getElementById('messagesList');
    var form = document.getElementById('sendMessageForm');
    var content = document.getElementById('messageContent');
    var convId = list.getAttribute('data-conversation-id');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var text = content.value.trim();
        if (!text) return;
        var fd = new FormData();
        fd.append('conversation_id', convId);
        fd.append('content', text);
        fetch('api/messages/send.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.error) { alert(res.error); return; }
                content.value = '';
                var m = res.message;
                var html = '<div class="message-item self" data-msg-id="' + m.id + '"><img src="uploads/avatars/' + (m.avatar || 'default_avatar.png') + '" alt="" class="msg-avatar"><div class="msg-body"><span class="msg-author">' + (m.nickname || m.username) + '</span><div class="msg-content">' + (m.content.replace(/</g, '&lt;').replace(/\n/g, '<br>')) + '</div><span class="msg-time">刚刚</span></div></div>';
                list.insertAdjacentHTML('beforeend', html);
                list.scrollTop = list.scrollHeight;
                lastMsgId = m.id;
            });
    });

    var lastEl = list.querySelector('.message-item:last-child');
    var lastMsgId = (lastEl && lastEl.getAttribute('data-msg-id')) ? lastEl.getAttribute('data-msg-id') : '0';

    setInterval(function() {
        fetch('api/messages/poll.php', { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!res.by_conversation || res.by_conversation[convId] <= 0) return;
                fetch('api/messages/new_since.php?conversation_id=' + convId + '&after_id=' + lastMsgId, { credentials: 'same-origin' })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        (data.messages || []).forEach(function(m) {
                            var isSelf = m.sender_id == <?php echo $userId; ?>;
                            var html = '<div class="message-item ' + (isSelf ? 'self' : '') + '" data-msg-id="' + m.id + '"><img src="uploads/avatars/' + (m.avatar || 'default_avatar.png') + '" alt="" class="msg-avatar"><div class="msg-body"><span class="msg-author">' + (m.nickname || m.username) + '</span><div class="msg-content">' + String(m.content).replace(/</g, '&lt;').replace(/\n/g, '<br>') + '</div><span class="msg-time">' + m.created_at + '</span></div></div>';
                            list.insertAdjacentHTML('beforeend', html);
                            lastMsgId = m.id;
                        });
                        if ((data.messages || []).length) list.scrollTop = list.scrollHeight;
                    });
                fetch('api/messages/mark_read.php', { method: 'POST', body: (function(){ var f=new FormData(); f.append('conversation_id', convId); return f; })(), credentials: 'same-origin' });
            });
    }, 4000);
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
