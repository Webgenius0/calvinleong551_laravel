<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            margin: 0;
            padding: 0;
        }
        .profile-container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .profile-header img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
        .profile-header h2 {
            margin: 10px 0 5px;
        }
        .profile-header p {
            color: #555;
            margin: 0;
        }
        .profile-details {
            margin-top: 20px;
        }
        .profile-details div {
            margin-bottom: 10px;
        }
        .profile-details strong {
            width: 120px;
            display: inline-block;
            color: #333;
        }
        .logout-btn {
            display: block;
            width: 100%;
            text-align: center;
            margin-top: 30px;
            padding: 10px;
            background: #e74c3c;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
        .logout-btn:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>

<div class="profile-container">
    <div class="profile-header">
        <img src="{{$user->avatar ?? 'avatar-placeholder.jpg'}}" alt="User Avatar">
        <h2>{{$user->name}}</h2>
        <p>{{$user->email}}</p>
    </div>

    <div class="profile-details">
       <h1>Welcome to your profile, {{$user->name}}!</h1>
    </div>

<div class="row col-md-12">
    <a href="{{ route('logout') }}" class="logout-btn">Logout</a>
    <a href="{{ route('delete.account') }}" class="logout-btn">Delete</a>
   
</div>

</body>
</html>
