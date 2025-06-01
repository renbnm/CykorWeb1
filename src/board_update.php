<?php
include "db_connect.php";
session_start();
if (!isset($_SESSION['id'])){
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST"){
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $id = $_POST['id'];

    if (empty($_POST['title'])) {
        echo "<script>alert('제목이 비어있습니다.'); history.back();</script>";
        exit;
    } else if (empty($_POST['content'])){
        echo "<script>alert('내용이 비어있습니다.'); history.back();</script>";
        exit;
    }
    $sql = "UPDATE posts SET title = '$title', content = '$content' WHERE id={$id}";
    $result = mysqli_query($connect, $sql);
    if ($result) {
        echo "<script>alert('수정이 완료되었습니다.'); location.href='index.php';</script>";
    } else {
        echo "<script>alert('수정에 실패하였습니다.'); history.back();</script>";
    }
    exit;

}
?>