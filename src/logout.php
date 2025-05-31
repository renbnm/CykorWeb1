<?php
session_start();
session_unset();
session_destroy();
?>
<script>
    alert("로그아웃되었습니다.");
    location.href = 'index.php';
</script>
