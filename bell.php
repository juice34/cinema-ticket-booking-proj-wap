<?php /* Bell icon. Usage: <?php include "bell.php"; ?> (inside a page where the user is logged in) */ ?>
<span id="nb" style="position:relative;display:inline-block;margin-right:14px;vertical-align:middle">
  <button type="button" id="nbBtn" aria-label="Notifications"
          style="background:none;border:0;cursor:pointer;padding:4px;position:relative;color:var(--text,#333);display:flex;align-items:center">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
    <span id="nbBadge" style="display:none;position:absolute;top:-4px;right:-6px;background:#e11;color:#fff;border-radius:10px;font-size:11px;font-weight:700;padding:1px 6px"></span>
  </button>
  <div id="nbBox" style="display:none;position:absolute;right:0;top:34px;width:300px;max-height:320px;overflow-y:auto;background:var(--surface,#fff);color:var(--text,#333);border:1px solid var(--border,#d6d6d6);border-radius:8px;box-shadow:0 4px 14px rgba(0,0,0,.15);z-index:999;text-align:left;font-size:14px;font-weight:400">
    <div id="nbList"></div>
  </div>
</span>
<script>
(function () {
  var btn = document.getElementById('nbBtn'), box = document.getElementById('nbBox'),
      list = document.getElementById('nbList'), badge = document.getElementById('nbBadge');

  function load() {
    fetch('api_notifications.php', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.success) return;
        badge.textContent = d.unread;
        badge.style.display = d.unread > 0 ? 'inline-block' : 'none';
        list.innerHTML = '';
        if (!d.notifications.length) {
          var empty = document.createElement('div');
          empty.style.cssText = 'padding:14px;color:var(--muted,#888)';
          empty.textContent = 'No notifications.';
          list.appendChild(empty);
          return;
        }
        d.notifications.forEach(function (n) {
          var isLink = n.link && /^pay\.php\?id=\d+$/.test(n.link);
          var item = document.createElement(isLink ? 'a' : 'div');
          if (isLink) item.href = n.link;
          item.style.cssText = 'display:block;color:inherit;text-decoration:none;padding:10px 14px;border-bottom:1px solid var(--border,#eee);' + (isLink ? 'cursor:pointer;' : '') + (n.is_read == 0 ? 'background:rgba(59,130,246,.14);font-weight:600;' : '');
          item.textContent = n.message;
          var t = document.createElement('div');
          t.style.cssText = 'font-size:12px;color:var(--muted,#888);font-weight:400;margin-top:3px';
          t.textContent = n.created_at;
          item.appendChild(t);
          if (isLink) {
            var go = document.createElement('div');
            go.style.cssText = 'font-size:12px;color:var(--link,#0033ff);font-weight:600;margin-top:3px';
            go.textContent = 'View ticket \u2192';
            item.appendChild(go);
          }
          list.appendChild(item);
        });
      }).catch(function () {});
  }

  btn.addEventListener('click', function () {
    var open = box.style.display === 'block';
    box.style.display = open ? 'none' : 'block';
    if (!open) {
      fetch('api_mark_read.php', { method: 'POST', credentials: 'same-origin' }).then(function () {
        badge.style.display = 'none';
      });
    } else { load(); }
  });
  document.addEventListener('click', function (e) {
    if (!document.getElementById('nb').contains(e.target)) { box.style.display = 'none'; }
  });

  load();
  setInterval(load, 10000);
})();
</script>