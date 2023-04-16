@extends('layouts.app')

@section('content')
<div class="container">
    <div class="flex">
        <div class="w-full">
            @include('layouts.success')
            <div class="bg-white rounded-md ">
                <div class="p-2">
                    <div class="flex ">
                        <div class="">
                            <img src="{{ Auth::user()->avatar }}" alt="avatar" class="mr-3 rounded-circle" width="45px" height="45px">
                            {{-- <b>{{ Auth::user()->name }}</b> --}}
                        </div>
                        <div class="w-full mt-1">
                            <a href="" class="form-control text-dark" data-toggle="modal" data-target="#add-questionModal">What is your question ?</a>
                        </div>
                    </div>
                </div>
            </div>

            <div id="answersHome">
                @foreach ($answers as $answer)
                    @php
                        //count view
                        views($answer)
                        ->cooldown(86400)
                        ->record();
                        //count question
                        views($answer->question)
                        ->cooldown(86400)
                        ->record();

                        //set share
                        $link = route('question.show',$answer->question);
                        $facebook = \Share::page($link)->facebook()->getRawLinks();
                        $twitter = \Share::page($link)->twitter()->getRawLinks();

                        //set credential
                        if($answer->user->credential){
                            $credential = $answer->user->credential;
                        }else{
                            $credential = \App\Http\Controllers\User\ProfileController::set_credential($answer->user);
                        }

                        //set vote status
                        if(auth()->user()->hasUpVoted($answer)){
                            $upvoted = "-fill";
                        }else{
                            $upvoted = "";
                        }

                        if(auth()->user()->hasDownVoted($answer)){
                            $downvoted = "-fill";
                        }else{
                            $downvoted = "";
                        }

                        //set follow status
                        if(auth()->user()->isFollowing($answer->user)){
                            $status = "Following";
                        }else{
                            $status = "Follow";
                        }

                    @endphp
                        <div class="mt-3 card" id="{{ $answer->user->name_slug }}">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3 row">
                                            <div class="col-1">
                                                <img src="{{ $answer->user->avatar }}" alt="avatar" class="rounded-circle" width="45px" height="45px">
                                            </div>

                                            <div class="col-11 ">
                                                <a href="{{ route('profile.show',$answer->user->name_slug) }}" class="text-dark"><b>{{  $answer->user->name }} </b></a> &#183;
                                                <a href="{{ route('follow',$answer->user->name_slug) }}">{{ $status }}</a>
                                                <div class="text-secondary">
                                                    {{ $credential }} &#183; {{ $answer->created_at->format('M d Y') }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <a href="{{ route('question.show',$answer->question->title_slug) }}" class="text-dark"><h5><b>{{ $answer->question->title }}</b></h5></a>
                                            </div>
                                        </div>
                                        <div class="mt-2 row">
                                            <div class="col-12">
                                                {{ $answer->text }}<br>
                                                @if ($answer->image)
                                                    <img src="{{ asset('img/' . $answer->image) }}" class="mt-2 mb-2 img-fluid" alt="image not found!">
                                                @else
                                                    <div class="mb-2"></div>
                                                @endif
                                                <small class="text-secondary">{{ views($answer)->count() }} views</small>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('answer.vote',['question' => $answer->question->title_slug,'answer' => $answer->id, 'vote' => 'upvote'])}}" class="mr-2 text-success" ><i class="bi bi-arrow-up-circle{{ $upvoted }}"></i> {{ $answer->upVoters()->count() }}</a>
                                                    <a href="{{ route('answer.vote',['question' => $answer->question->title_slug,'answer' => $answer->id, 'vote' => 'downvote'])}}" class="mr-4 text-danger" ><i class="bi bi-arrow-down-circle{{ $downvoted }}"></i> {{ $answer->downVoters()->count() }}</a>
                                                    <a href="{{ $answer->question->title_slug ."#". $answer->user->name_slug }}" class="text-secondary"><i class="bi bi-chat"></i> {{ $answer->comments->count() }}</a>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="float-right btn-group" role="group">
                                                <a href="" class="text-dark" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="bi bi-share"></i></a>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                                                        <a class="dropdown-item" href="{{ $facebook . '#'.$answer->user->name_slug}} '. '" target="_blank"><i class="mr-2 bi bi-facebook"></i>Facebook</a>
                                                        <a class="dropdown-item" href="{{ $twitter . '#'.$answer->user->name_slug}}'.'" target="_blank"><i class="mr-2 bi bi-twitter"></i>Twitter</a>
                                                        <a class="dropdown-item" href="javascript: void(0)" onclick="copy()" id="copyLink" data-attr="{{ $answer->question->title_slug ."#". $answer->user->name_slug }}">Copy link</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                @endforeach
            </div>

            <div class="text-center">
                <button class="mt-2 btn btn-secondary btn-sm moreHome rounded-pill" data-page="2" data-link="/home?page=" data-div="#answersHome">More</button>
            </div>

        </div>

        <div class="hidden col-4 md:block">
            <x-popular-topic/>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>

    let env_url = "{{ env('APP_URL') }}";

    $(".moreHome").click(function () {
        $div = $($(this).data('div')); //div to append
        $link = $(this).data('link'); //current URL

        $page = $(this).data('page'); //get the next page #
        $href = $link + $page; //complete URL
        $.get($href, function (response) { //append data
            $html = $(response).find("#answersHome").html();
            $div.append($html);
        });

        $(this).data('page', (parseInt($page) + 1)); //update page #
    });

    //script for copy link to clipboard
    function copy() {
        let dummy = document.createElement('input');
        let href = $('#copyLink').attr('data-attr');
        let text = env_url + href;

        document.body.appendChild(dummy);
        dummy.value = text;
        dummy.select();
        document.execCommand('copy');
        document.body.removeChild(dummy);

        alert('Link copied to clipboard');
    }

</script>
@endsection

