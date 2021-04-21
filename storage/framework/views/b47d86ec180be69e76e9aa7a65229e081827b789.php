<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;600&display=swap" rel="stylesheet">

        <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" type="text/css" rel="stylesheet" />

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Nunito', sans-serif;
                font-weight: 200;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 84px;
            }

            .links > a {
                color: #636b6f;
                padding: 0 25px;
                font-size: 13px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }
            .chat_people img{
                height: 50px;
                width: 50px;
            }
            .chat_people{
                display: flex;
            }
            .m-b-md {
                margin-bottom: 30px;
            }
        </style>
    </head>
    <body>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.3.0/socket.io.js"></script>
        <script type="text/javascript" src="<?php echo e(asset('js/testSocket.js')); ?>"></script>
        
        <div class="">
            <?php if(Route::has('login')): ?>
                <div class="top-right links">
                    <?php if(auth()->guard()->check()): ?>
                        <a href="<?php echo e(url('/home')); ?>">Home</a>
                    <?php else: ?>
                        <a href="<?php echo e(route('login')); ?>">Login</a>

                        <?php if(Route::has('register')): ?>
                            <a href="<?php echo e(route('register')); ?>">Register</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="container">
                <div class="inbox_chat mt-5">
                    <div class="chat_list active_chat">
                      <div class="chat_people">
                        <div class="chat_img"> <img src="https://ptetutorials.com/images/user-profile.png" alt="sunil"> </div>
                        <div class="chat_ib">
                          <h6>Sunil Rajput <span class="chat_date">Dec 25</span></h6>
                          <p>Test, which is a new approach to have all solutions 
                            astrology under one roof.</p>
                        </div>
                        <div class="send-message">
                            <button type="button" class="btn btn-primary btn-sm" data-user="1" data-message="hi I am user 1">Send</button>
                        </div>
                      </div>
                    </div>
                    <div class="chat_list">
                      <div class="chat_people">
                        <div class="chat_img"> <img src="https://ptetutorials.com/images/user-profile.png" alt="sunil"> </div>
                        <div class="chat_ib">
                          <h6>Sunil Rajput <span class="chat_date">Dec 25</span></h6>
                          <p>Test, which is a new approach to have all solutions 
                            astrology under one roof.</p>
                        </div>
                        <div class="send-message">
                            <button type="button" class="btn btn-primary btn-sm" data-user="2" data-message="hi I am user 2">Send</button>
                        </div>
                      </div>
                    </div>
                    <div class="chat_list">
                      <div class="chat_people">
                        <div class="chat_img"> <img src="https://ptetutorials.com/images/user-profile.png" alt="sunil"> </div>
                        <div class="chat_ib">
                          <h6>Sunil Rajput <span class="chat_date">Dec 25</span></h6>
                          <p>Test, which is a new approach to have all solutions 
                            astrology under one roof.</p>
                        </div>
                        <div class="send-message">
                            <button type="button" class="btn btn-primary btn-sm" data-user="3" data-message="hi I am user 3">Send</button>
                        </div>
                      </div>
                    </div>
                    <div class="chat_list">
                      <div class="chat_people">
                        <div class="chat_img"> <img src="https://ptetutorials.com/images/user-profile.png" alt="sunil"> </div>
                        <div class="chat_ib">
                          <h6>Sunil Rajput <span class="chat_date">Dec 25</span></h6>
                          <p>Test, which is a new approach to have all solutions 
                            astrology under one roof.</p>
                        </div>
                        <div class="send-message">
                            <button type="button" class="btn btn-primary btn-sm" data-user="4" data-message="hi I am user 4">Send</button>
                        </div>
                      </div>
                    </div>
                    <div class="chat_list">
                      <div class="chat_people">
                        <div class="chat_img"> <img src="https://ptetutorials.com/images/user-profile.png" alt="sunil"> </div>
                        <div class="chat_ib">
                          <h6>Sunil Rajput <span class="chat_date">Dec 25</span></h6>
                          <p>Test, which is a new approach to have all solutions 
                            astrology under one roof.</p>
                        </div>
                        <div class="send-message">
                            <button type="button" class="btn btn-primary btn-sm" data-user="5" data-message="hi I am user 5">Send</button>
                        </div>
                      </div>
                    </div>
                    <div class="chat_list">
                      <div class="chat_people">
                        <div class="chat_img"> <img src="https://ptetutorials.com/images/user-profile.png" alt="sunil"> </div>
                        <div class="chat_ib">
                          <h6>Sunil Rajput <span class="chat_date">Dec 25</span></h6>
                          <p>Test, which is a new approach to have all solutions 
                            astrology under one roof.</p>
                        </div>
                        <div class="send-message">
                            <button type="button" class="btn btn-primary btn-sm" data-user="6" data-message="hi I am user 6">Send</button>
                        </div>
                      </div>
                    </div>
                    <div class="chat_list">
                      <div class="chat_people">
                        <div class="chat_img"> <img src="https://ptetutorials.com/images/user-profile.png" alt="sunil"> </div>
                        <div class="chat_ib">
                          <h6>Sunil Rajput <span class="chat_date">Dec 25</span></h6>
                          <p>Test, which is a new approach to have all solutions 
                            astrology under one roof.</p>
                        </div>
                        <div class="send-message">
                            <button type="button" class="btn btn-primary btn-sm" data-user="7" data-message="hi I am user 7">Send</button>
                        </div>
                      </div>
                    </div>
                  </div>
            </div>
        </div>
        <script type="text/javascript">
              socket.emit('joinroom', { event: 'chat-users', room: 1 });
              socket.on('connect', () => {
                  socket.emit('joinroom', { event: 'chat-users', room: '1' });
              });
             
          </script>
    </body>
</html>
<?php /**PATH C:\xampp\htdocs\Laravel\TapeTalkGit\TapeTalk\resources\views/welcome.blade.php ENDPATH**/ ?>