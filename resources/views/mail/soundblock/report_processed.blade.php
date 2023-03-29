@extends("mail.layouts.soundblock")

@section("content")
    <x-head-line class="soundblock-email-headline" title="Royalties Posted"/>

    <span class="font-book">Hello, {{ $userName }}!</span><br>
    <span class="font-book">We have received and processed royalty payments from the following platforms:</span>
    <p></p>

    <ul>
        @foreach ($arrData as $key => $arrPlatformData)
            <li class="font-book">
                {{ $arrPlatformData["platform_name"] }} ({{ $arrPlatformData["date_starts"] }}-{{ $arrPlatformData["date_ends"] }})
                @if(isset($arrPlatformData["memo"]))
                    <br>
                    <span class="font-book">{{ $arrPlatformData["memo"] }}</span>
                @endif
            </li>
            <br>
        @endforeach
    </ul>

    <p></p>
    <span class="font-book">Any royalties earned are available in your Soundblock account for further review and withdrawal. Thank you for using Soundblock!</span>

    <p style="text-align: center;">
        <x-button class="soundblock-email-button" text="Soundblock Payments" :link="$link"/>
    </p>
    <p>
        <small><a style="color:dodgerblue;" href="{{ $link }}">{{ $link }}</a></small>
    </p>
@endsection
