<?php
session_start();
if(!isset($_SESSION['id'])) {
    echo "<script>location.replace('login.php');</script>";
}
else {
    $name = $_SESSION['id'];
}
?>
<!DOCTYPE html>
<html>
<body>
    <div class="base">
        <h2><?php echo "반갑습니다 $name 님!"?><h2>
        <button type="button", class="btn", oneclick="location.href='logout.php'">로그아웃</button>
    </div>
</body>