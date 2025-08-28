<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>


<div style="font-family: Helvetica,Arial,sans-serif;min-width:1000px;overflow:auto;line-height:2">
    <div style="margin:50px auto;width:70%;padding:20px 0">
        <div style="border-bottom:1px solid #eee">
            <a href="{{ config('app.url') }}"
               style="font-size:1.4em;color: #00466a;text-decoration:none;font-weight:600">
                {{ config('app.name') }}
            </a>
        </div>

        <p style="font-size:1.1em">Hi {{ $review->user->name }},</p>

        <p>
            One of your reviews has been reported by another user. Below are the details of the report:
        </p>

        <div style="background: #f9f9f9; padding: 15px; border-radius: 5px;">
            <p><strong>📍 Location:</strong> {{ $location?->name ?? 'N/A' }}</p>
            <p><strong>📝 Your Review:</strong> {{ $review->comment }}</p>
            <p><strong>🕒 Review Date:</strong> {{ $review->created_at->format('F j, Y') }}</p>
        </div>

        <div style="margin-top: 20px; background: #fff8f0; padding: 15px; border-radius: 5px;">
            <p><strong>🚨 Reported By:</strong> {{ $reporter->name }}</p>
            <p><strong>🔍 Reason:</strong> {{ $report->reason }}</p>
            <p><strong>📄 Description:</strong> {{ $report->description }}</p>
            <p><strong>🕒 Report Date:</strong> {{ $report->created_at->format('F j, Y') }}</p>
        </div>

        @if($report->image)
            <div style="margin-top: 20px;">
                <p><strong>🖼️ Attached Image:</strong></p>
                <img src="{{ $report->image }}" style="max-width: 100%; border: 1px solid #ccc; border-radius: 4px;">
            </div>
        @endif

        <p style="font-size:0.9em;">Regards,<br />{{ config('app.name') }}</p>

        <hr style="border:none;border-top:1px solid #eee" />

        <div style="float:right;padding:8px 0;color:#aaa;font-size:0.8em;line-height:1;font-weight:300">
            <p>{{ $company_name }}</p>
            <p>{{ $company_location }}</p>
        </div>
    </div>
</div>


</body>
</html>
