@extends("mail.layouts.merch", ["app" => $app])

@section("content")
    <p>
        @if (isset($arrJsonData))
        @if (!empty($arrJsonData))
        @if(isset($arrJsonData["message_type"]) && $arrJsonData["message_type"]=="json")

            @foreach($arrJsonData as $jsonKey => $jsonValue)
            @if($jsonKey=="message_type")
            @continue
            @endif
                <span>
                    <b>{{ ucfirst(str_replace("_", " ", $jsonKey)) }} - </b> {{ $jsonValue }}<br>
                </span>
            @endforeach
            @endif
            @if(isset($arrJsonData["message_type"]) && $arrJsonData["message_type"]=="text")
            <p>
            <span>
                {{$arrJsonData["message_body"]}}
            </span>
        </p>
            @endif

        @endif
    @endif
    </p>

    <p>
        <span>
            <b>Email - </b> {{ $correspondence->email }}<br>
        </span>
    </p>
    <p>
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
@endsection
