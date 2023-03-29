@extends("mail.layouts.soundblock")

@section("content")
    <x-head-line class="soundblock-email-headline" title="Support Ticket Opened"/>
    <p style="word-break: break-word;">Thank you for contacting Soundblock. We have received your support ticket and will reply as soon as possible.</p>

    @if ($link)
        <x-p text="Please follow the link below to view your support ticket online."/>
        <p style="text-align: center;">
            <button class=soundblock-email-button>
                <a style="color: white;" href="{{$link}}">Support</a>
            </button>
        </p>
        <p>
            <small><a style="color:dodgerblue;" href="{{ $link }}">{{ $link }}</a></small>
        </p>
    @endif
@endsection
