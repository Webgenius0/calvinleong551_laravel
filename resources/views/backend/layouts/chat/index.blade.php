@extends('backend.app')
@push('styles')
@endpush
@section('content')
<!--app-content open-->
<div class="app-content main-content mt-0">
    <div class="side-app">

        <!-- CONTAINER -->
        <div class="main-container container-fluid">


            <!-- PAGE-HEADER -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Chat</h1>
                </div>
                <div class="ms-auto pageheader-btn">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Apps</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Chat</li>
                    </ol>
                </div>
            </div>
            <!-- PAGE-HEADER END -->

            <!-- Row -->
            <div class="row row-deck">
                <div class="col-sm-12 col-md-4">
                    <div class="card  overflow-scroll">
                        <div class="main-content-app pt-0">
                            <div class="main-content-left main-content-left-chat">

                                <!-- main-chat-header -->
                                <div class="card-body overflow-scroll border-bottom">
                                    <div class="input-group mb-2">
                                        <form action="" method="get">
                                            <div class="input-group">
                                                <input name="keyword" type="text" id="keyword" class="form-control" placeholder="Search ...">
                                                <button type="button" class="btn btn-primary text-white" onclick="userSearch();">Search</button>
                                                <button type="button" class="btn btn-secondary text-white" onclick="userList();">Refresh</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- main-chat-list -->
                                <div class="tab-content main-chat-list flex-2">
                                    <div class="tab-pane active" id="ChatList">
                                        <div class="main-chat-list tab-pane" id="userList"></div>
                                        <!-- main-chat-list -->
                                    </div>
                                </div>
                                <!-- main-chat-list -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-8">
                    <div class="card">
                        <div class="main-content-app pt-0">

                            <div class="main-content-body main-content-body-chat h-100 d-none" id="ChatBox">
                                <div class="main-chat-header pt-3 d-block d-sm-flex">
                                    <div class="main-img-user online" id="ReceiverImage"></div>
                                    <div class="main-chat-msg-name mt-2">
                                        <p class="mb-0" id="ReceiverName" onclick="userChat($('#ReceiverId').val());" style="cursor: pointer;">User</p>
                                        <small class="me-3" id="ReceiverRoll">Roll</small>
                                    </div>
                                </div>
                                <!-- main-chat-header -->
                                <div class="main-chat-body flex-2" id="ChatBody">
                                    <div class="content-inner" id="ChatContent" style="max-height: 500px; overflow-y: auto;"></div>
                                </div>
                                <div class="main-chat-footer pt-5 pb-5">
                                    <label for="File" id="FileLabel" class="btn btn-primary brround" style="margin-left:20px; margin-top:8px"><i class="bi bi-image"></i></label>
                                    <input type="file" id="File" hidden accept=".jpg,.jpeg,.png,.gif">
                                    <input class="form-control" placeholder="Type your message here..." type="text" id="Text">
                                    <input type="text" hidden id="ReceiverId" />
                                    <input type="text" hidden id="RoomId" />
                                    <button type="button" class="btn btn-icon btn-primary brround" onclick="sendMessage($('#ReceiverId').val())"><i class="bi bi-send"></i></button>
                                    <button type="button" class="btn btn-icon btn-primary brround" style="margin-left:10px" onclick="formClear()"><i class="bi bi-arrow-clockwise"></i></button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- CONTAINER CLOSED -->
@endsection



@push('scripts')
<!-- Internal Chat js-->
<!-- <script src="{{ asset('backend/js/chat.js') }}"></script> -->
<script src="https://cdn.jsdelivr.net/npm/dayjs/dayjs.min.js"></script>
<script>
    function userList() {
        NProgress.start();
        $.ajax({
            url: `{{ route('admin.chat.list') }}`,
            type: "GET",
            success: function(response) {
                NProgress.done();
                $('#userList').empty();
                $.each(response.data.users, function(index, value) {
                    let senderAvatar = value.avatar ? `{{ asset('${value.avatar}') }}` : "{{ asset('default/profile.jpg') }}";
                    $('#userList').append(`
                        <a class="media new" href="javascript:void(0)" onclick="userChat(${value.id})">
                            <div class="main-img-user online">
                                <img alt="avatar" src="${senderAvatar}">
                                ${value.is_online ? '<span class="dot-label bg-success"></span>' : '<span class="dot-label bg-danger"></span>'}
                            </div>
                            <div class="media-body">
                                <div class="media-contact-name">
                                    <span>${value.name}</span>
                                </div>
                                    <span class="time">${value.last_chat.short_text}</span>
                                </div>
                                <span class="time">${value.last_chat.humanize_date}</span>
                            </div>
                        </a>
                    `);
                });
            },
            error: function(xhr, status, error) {
                console.log('Error sending message:', error);
            }
        });
    }

    userList();

    function userSearch() {
        NProgress.start();
        $('#userList').empty();
        let keyword = $('#keyword').val();
        $.ajax({
            url: `{{ route('admin.chat.search') }}?keyword=${keyword}`,
            type: "GET",
            success: function(response) {
                NProgress.done();
                $.each(response.data.users, function(index, value) {
                    let senderAvatar = value.avatar ? `{{ asset('${value.avatar}') }}` : "{{ asset('default/profile.jpg') }}";
                    $('#userList').append(`
                        <a class="media new" href="javascript:void(0)" onClick="userChat(${value.id})">
                            <div class="main-img-user online">
                                <img alt="avatar" src="${senderAvatar}">
                            </div>
                            <div class="media-body">
                                <div class="media-contact-name">
                                    <span>${value.name}</span>
                                </div>
                                <span class="time">${value.email}</span>
                            </div>
                        </a>
                    `);
                });
            },
            error: function(xhr, status, error) {
                console.log('Error sending message:', error);
            }
        });
    }

    function userChat(receiver_id) {
        NProgress.start();
        $.ajax({
            url: `{{ route('admin.chat.conversation', ':id') }}`.replace(':id', receiver_id),
            type: "GET",
            success: function(response) {
                NProgress.done();
                $('#ChatContent').empty();
                $('#ReceiverId').val(receiver_id);
                $('#ReceiverName').text(response.data.receiver.name);
                $('#ReceiverRoll').text(response.data.receiver.role);
                $('#RoomId').val(response.data.room.id);
                window.sessionStorage.setItem('room_id', response.data.room.id);
                $('#ChatBox').removeClass('d-none');

                if ($('#selectUser' + receiver_id).length) {
                    $('.selected').not('#selectUser' + receiver_id).removeClass('selected');
                    $('#selectUser' + receiver_id).addClass('selected');
                }

                let receiverAvatar = response.data.receiver.avatar ? `{{ asset('${response.data.receiver.avatar}') }}` : "{{ asset('default/profile.jpg') }}";
                let senderAvatar = response.data.sender.avatar ? `{{ asset('${response.data.sender.avatar}') }}` : "{{ asset('default/profile.jpg') }}";

                $('#ReceiverImage').html(`<img alt="avatar" src="${receiverAvatar}">`);

                let senderClass = 'media flex-row-reverse chat-right';
                let receiverClass = 'media chat-left';

                response.data.chat.forEach(chat => {
                    console.log(chat);
                    let chatClass = chat.sender_id == `{{auth('web')->user()->id}}` ? senderClass : receiverClass;
                    let avatar = chat.sender_id == `{{auth('web')->user()->id}}` ? senderAvatar : receiverAvatar;
                    $('#ChatContent').append(`
                    <div class="${chatClass}">
                        <div class="main-img-user online"><img alt="avatar" src="${avatar}"></div>
                        <div class="media-body">
                            ${chat.text ? `<div class="main-msg-wrapper">${chat.text}</div>` : ''}
                            ${chat.file ? `<div class="main-msg-wrapper"><a href="${chat.file}" target="_blank"><img src="${chat.file}"></a></div>` : ''}
                            <div>
                                <span>${chat.humanize_date}</span>
                            </div>
                        </div>
                    </div>
                `);
                });

                $('#ChatContent').scrollTop($('#ChatContent')[0].scrollHeight);
            },
            error: function(xhr, status, error) {
                console.error('Error sending message:', error);
            }
        });
    }

    $('#File').on('change', function() {
        let file = this.files[0];
        let reader = new FileReader();
        reader.onload = function(e) {
            $('#FileLabel').html(`<img src="${e.target.result}" style="width: 20px; height: 20px;"/>`);
        };
        reader.readAsDataURL(file);
    });

    function formClear() {
        NProgress.start();
        $('#FileLabel').html(`<i class="bi bi-image"></i>`);
        $('#File').val('');
        $('#Text').val('');
        NProgress.done();
        toastr.success('Form Clear');
    }

    function sendMessage(receiver_id) {
        NProgress.start();
        let text = $('#Text').val() || null;
        let file = $('#File')[0].files[0] || null;
        if (text !== null || file !== null) {
            let formData = new FormData();
            if (text !== null) {
                formData.append('text', text);
            }
            if (file !== null) {
                formData.append('file', file);
            }

            $.ajax({
                url: `{{ route('admin.chat.send', ':id') }}`.replace(':id', receiver_id),
                type: "POST",
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    NProgress.done();
                    $('#Text').val('');
                    $('#File').val('');
                    $('#FileLabel').html(`<i class="bi bi-image"></i>`);
                    userChat(receiver_id);
                    userList();
                },
                error: function(xhr, status, error) {
                    console.log('Error sending message:', error);
                }
            });
        }
    }

    setInterval(() => {
        userList();
    }, 300000);

    /* document.addEventListener('DOMContentLoaded', function() {
        const roomId = window.sessionStorage.getItem('room_id');
        Echo.private(`chat-room.${roomId}`).listen('MessageSendEvent', function(e) {
            userChat(document.getElementById('ReceiverId').value);
            userList();
        });
    }); */

    var user_id = `{{ auth('web')->check() ? auth('web')->user()->id : null }}`;

    if (user_id) {
        document.addEventListener('DOMContentLoaded', function() {
            Echo.private(`chat-receiver.${user_id}`).listen('MessageSendEvent', function(e) {
                toastr.success(e.data.text ?? "File Sent");
                let receiver_id = document.getElementById('ReceiverId').value;
                userChat(receiver_id);
                userList();
            });
        });
    }
</script>
@endpush