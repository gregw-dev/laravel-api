@extends("mail.layouts.soundblock")

@section("content")
    <x-head-line class="soundblock-email-headline" title="Support Ticket Reply"/>
    <p style="word-break: break-word;">You have a new reply to your support ticket.</p>

    @if ($sound_url)
        <x-p text="Please follow the link below to view your support ticket online."/>
        <p style="text-align: center;">
            <button class=soundblock-email-button>
                <a style="color: white;" href="{{$sound_url}}">Support</a>
            </button>
        </p>
        <p>
            <small><a style="color:dodgerblue;" href="{{ $sound_url }}">{{ $sound_url }}</a></small>
        </p>
    @else
        <p style="word-break: break-word;">Please <a href="{{$url}}">sign into your account</a> and click the support link at the top of any page to view this message.</p>
    @endif
@endsection
