<?php

include 'components/connect.php';

if(isset($_COOKIE['user_id'])){
   $user_id = $_COOKIE['user_id'];
}else{
   $user_id = '';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>about</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>

<?php include 'components/user_header.php'; ?>

<!-- about section starts  -->

<section class="about">

   <div class="row">

      <div class="image">
         <img src="images/about-img.svg" alt="">
      </div>

      <div class="content">
         <h3>why choose us?</h3>
         <p>Unlock your full potential with our expertly designed courses tailored for modern learners. Whether you're starting fresh or looking to upskill, our interactive and flexible programs are built to fit your goals and schedule.</p>
        <a href="courses.php" class="inline-btn">our courses</a>
      </div>

   </div>

   <div class="box-container">

      <div class="box">
         <i class="fas fa-graduation-cap"></i>
         <div>
            <h3>+10</h3>
            <span>online courses</span>
         </div>
      </div>

      <div class="box">
         <i class="fas fa-user-graduate"></i>
         <div>
            <h3>+3</h3>
            <span>brilliants students</span>
         </div>
      </div>

      <div class="box">
         <i class="fas fa-chalkboard-user"></i>
         <div>
            <h3>+5</h3>
            <span>expert teachers</span>
         </div>
      </div>

      <div class="box">
         <i class="fas fa-briefcase"></i>
         <div>
            <h3>100%</h3>
            <span>Opportunities</span>
         </div>
      </div>

   </div>

</section>



<section class="reviews">

   <h1 class="heading">student's reviews</h1>

   <div class="box-container">

      <div class="box">
      <p>This course helped me improve my spoken English and gave me the confidence to join international meetings. Highly recommended for working professionals!</p>
      <div class="user">
            <img src="images/pic-2.jpg" alt="">
            <div>
               <h3>Anda</h3>
               <div class="stars">
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star-half-alt"></i>
               </div>
            </div>
         </div>
      </div>

      <div class="box">
         <p>Bu kurs orqali ingliz tilim ancha yaxshilandi. O‘qituvchilar tushunarli tushuntiradi, mashg‘ulotlar qiziqarli. Barchaga tavsiya qilaman!</p>
      <div class="user">
            <img src="images/pic-3.jpg" alt="">
            <div>
               <h3>Azizbek</h3>
               <div class="stars">
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star-half-alt"></i>
               </div>
            </div>
         </div>
      </div>

      <div class="box">
   <p>课程内容丰富，老师讲解清晰，帮助我提升了英语听说能力。对我申请海外工作非常有帮助。</p>
      <div class="user">
            <img src="images/pic-4.jpg" alt="">
            <div>
               <h3>Li Wei (李伟)</h3>
               <div class="stars">
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star-half-alt"></i>
               </div>
            </div>
         </div>
      </div>

      <div class="box">
<p>수업이 체계적이고 실용적이에요. 특히 회화 실력이 많이 늘었어요. 영어에 자신감이 생겼습니다!</p>
      <div class="user">
            <img src="images/pic-5.jpg" alt="">
            <div>
               <h3>유나</h3>
               <div class="stars">
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star-half-alt"></i>
               </div>
            </div>
         </div>
      </div>

      <div class="box">
   <p>Honestly, I thought English was boring before, but this site made it fun. The videos and practice tools are super helpful. Love it!</p>
      <div class="user">
            <img src="images/pic-6.jpg" alt="">
            <div>
               <h3>Anderson</h3>
               <div class="stars">
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star-half-alt"></i>
               </div>
            </div>
         </div>
      </div>

      <div class="box">
   <p>I didn’t know much English before. But now I can talk with friends and understand movies. Thank you for making it easy</p>
      <div class="user">
            <img src="images/pic-7.jpg" alt="">
            <div>
               <h3>Alice</h3>
               <div class="stars">
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star-half-alt"></i>
               </div>
            </div>
         </div>
      </div>

   </div>

</section>

<!-- reviews section ends -->










<?php include 'components/footer.php'; ?>

<!-- custom js file link  -->
<script src="js/script.js"></script>
   
</body>
</html>