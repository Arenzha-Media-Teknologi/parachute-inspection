<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- <title>Lampiran Pemeriksaan Parasut</title> -->
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
        }

        body {
            font-family: sans-serif;
            font-size: 10px;
            background-color: #f9f9f9;
            /* margin: 20px; */
        }

        .card-wrapper {
            padding: 20px;
            /* margin halaman */
            /* height: 100vh; */
            box-sizing: border-box;
            page-break-after: always;
        }

        .card {
            page-break-inside: avoid;
            break-inside: avoid;
            border: 2px solid green;
            border-radius: 8px;
            /* sudut melengkung opsional */
            padding: 20px;
            /* ruang di dalam card */
            /* margin: 20px auto; */
            margin-bottom: 20px;
            /* ruang di luar card */
            background-color: #fff;
            /* max-width: 900px; */
            /* width: 100%; */
            /* height: 100%; */
            /* box-sizing: border-box; */
            /* agar padding tidak melebihi 100% */

        }

        h2 {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .info {
            margin-bottom: 15px;
        }

        .info table {
            width: 100%;
        }

        .info td {
            padding: 4px;
        }

        table.inspection-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: auto;
        }

        table.inspection-table th,
        table.inspection-table td {
            /* border: 1px solid #000; */
            /* border: 1px solid #ccc; */
            /* padding: 6px; */
            padding: 3px;
            text-align: left;
        }

        table.inspection-table th {
            /* background-color: #eee; */
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>

    <div class="card-wrapper">
        <div class="card">
            <table style="width: 100%; margin-bottom: 50px;">
                <tr>
                    <td style="width: 30%; vertical-align: top;">
                        <p style="margin: 0; text-align: center; font-weight: bold;">DEPO PEMELIHARAAN 70</p>
                        <p style="margin: 0; text-align: center; font-weight: bold;">SATUAN PEMELIHARAAN 72</p>
                        <div style="border-bottom: 2px solid black; width: 100%; margin-top: 5px;"></div>
                    </td>
                    <td style="width: 35%; vertical-align: top;"> </td>
                    <td style="width: 30%; text-align: right; vertical-align: top;">
                        <p style="margin: 0; text-align: center; font-weight: bold;">Lampiran Nota Dinas Dansathar 72</p>
                        <p style="margin: 0; text-align: center; font-weight: bold;">Nomor B/ND- &emsp; /VI/2024/Sathar 72</p>
                        <div style="border-bottom: 2px solid black; width: 100%; margin-top: 5px; margin-left: auto;"></div>
                    </td>
                </tr>
            </table>
            <h2>DOKUMENTASI KERUSAKAN PARASUT</h2>
            <div class="text-center" style="padding-bottom: 50px; display: flex; justify-content: center;">
                <table class="inspection-table" style="width: 80%;">
                    <tbody>
                        @forelse($data as $item)
                        <tr>
                            <td style=" text-align: center; font-size: medium; width: 5%;"><b>{{ $loop->iteration }}.</b></td>
                            <td style="text-align: left; font-size: medium;">
                                <b>PN : </b>{{ $item->parachute->part_number ?? '-' }} &ensp; <b>SN : </b>{{ $item->parachute->serial_number ?? '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td style="text-align: left; font-size: medium;">
                                @foreach($item->items as $subitem)

                                @php
                                $path = storage_path('app/public/' . $subitem->image_url);
                                $type = pathinfo($path, PATHINFO_EXTENSION);
                                $data = file_get_contents($path);
                                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                                @endphp
                                <p>
                                    <img src="{{ $base64 }}" style="max-width: 400px; max-height: 250px;" />
                                    <!-- <img src="{{ asset('storage/' . $subitem->image_url) }}" alt="Preview" style="max-width: 400px; max-height: 300px;" /> -->
                                </p>
                                <b> Kerusakan : </b> <br>
                                <p> - {{ $subitem->description }} </p>

                                @endforeach
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" style="text-align: center; ">Data tidak tersedia</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>

</html>