<?php
include "db_connect.php";
session_start();
if (!isset($_SESSION['id'])){
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit;
}

$num = $_GET['id'];
$sql = "SELECT * FROM posts WHERE id ={$num}";
$result = mysqli_query($connect, $sql);
$post = mysqli_fetch_assoc($result);

if (!$post) {
    echo "<script>alert('게시물이 존재하지 않습니다.'); location.href='index.php';</script>";
    exit;
}

if ($post['author'] != $_SESSION['id']){
    echo "<script>alert('권한이 없습니다.'); history.back();</script>";
    exit;
}

$sql2 = "DELETE FROM posts WHERE id ={$num}";
$result_delete = mysqli_query($connect, $sql2);

if ($result_delete) {
    echo "<script>alert('삭제가 완료되었습니다.'); location.href='index.php';</script>";
} else {
    echo "<script>alert('삭제에 실패하였습니다.'); history.back();</script>";
}
exit;
?>