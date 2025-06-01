<?php
session_start();
include "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST"){
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $author = $_SESSION['id'];

    if (empty($_POST['title'])) {
        echo "<script>alert('제목이 비어있습니다.'); history.back();</script>";
        exit;
    } else if (empty($_POST['content'])){
        echo "<script>alert('내용이 비어있습니다.'); history.back();</script>";
        exit;
    }
    $sql = "INSERT INTO posts (title, content, author) VALUES ('$title', '$content','$author')";
    $result = mysqli_query($connect, $sql);
    if ($result) {
        echo "<script>alert('작성이 완료되었습니다.'); location.href='index.php';</script>";
    } else {
        echo "<script>alert('작성에 실패하였습니다.'); history.back();</script>";
    }
    exit;

}


?>