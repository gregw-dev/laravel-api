@extends('mail.layouts.merch', ['app' => $app])

@section('content')
    <p>
        @if (!empty($arrJsonData))
            @if($arrJsonData["message_type"] == "json")
                @foreach($arrJsonData as $jsonKey => $jsonValue)
                    @if($jsonKey == "message_type")
                        @continue
                    @endif
                    <span>
                        <b>{{ ucfirst(str_replace("_", " ", $jsonKey)) }} - </b> {{ $jsonValue }}<br>
                    </span>
                @endforeach
            @endif

            @if($arrJsonData["message_type"] == "text")
                <p>
                    <span>
                        {{$arrJsonData["message_body"]}}
                    </span>
                </p>
            @endif
        @endif

        @if(isset($arrJsonData["message_type"]) && $arrJsonData["message_type"]=="json")
            <span>
                <b>Email - </b> {{ $correspondence->email }}<br>
            </span>
        @endif

        <span>
            <b>Subject - </b> {{ $correspondence->email_subject }}<br>
        </span>
    </p>

    @if (!empty($attachments))
        <p>
            <h4>Attachments:</h4>
            @foreach ($attachments as $attachment)
                <span>
                    {{ $attachment["name"] }} - <b><a href="{{ $attachment["url"] }}" style="color: dodgerblue;">Download</a></b><br>
                </span>
            @endforeach
        </p>
    @endif

    @if($strConfirmationPage)
        <p>
            <small><a style="color:dodgerblue;" href="{{ $strConfirmationPage }}">Click Here to View on the Web</a></small>
        </p>
    @endif
@endsection
