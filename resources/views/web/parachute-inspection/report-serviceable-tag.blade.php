<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- <title>Unserviceable TAG - Preview</title> -->
    <style>
        @page {
            size: 148mm 105mm;
            /* Ukuran A6 landscape */
            margin: 10mm;
            /* Margin halaman cetak */
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                font-family: sans-serif;
                font-size: 10px;
            }

            .print-page {
                width: 100%;
                max-width: 128mm;
                /* 148mm - 2x10mm */
                margin: 0 auto;
                page-break-after: always;
                box-sizing: border-box;

                /* Jarak isi ke tepi */
                padding: 5mm 5mm 0mm 5mm;
                /* Atas, Kanan, Bawah, Kiri */
            }
        }

        @media screen {
            body {
                font-family: sans-serif;
                font-size: 10px;
                background: #f5f5f5;
                /* Warna latar preview */
            }

            .print-page {
                width: 100%;
                max-width: 128mm;
                margin: 20px auto;
                background: #fff;
                box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
                padding: 20px;
            }
        }

        table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }

        td {
            border: 1px solid #000;
            padding: 5px;
            word-wrap: break-word;
        }
    </style>

</head>

<body>
    <div class="wrapper">
        <div class="print-page">
            <table>
                <tr>
                    <td colspan="4" style="text-align: center; width: 70%;">
                        <p><span style="font-size: small;"><u><b>{{ $title }}</b></u></span><br>
                            <span><b>Label Barang Perbaikan</b></span>
                        </p>
                    </td>
                    <td colspan="2" style="text-align: center; width: 30%;">
                        <p><b>BENTUK</b></span> &ensp; 24503</span> </p>
                    </td>
                </tr>

                <tr>
                    <td colspan="3" style="text-align: left; width: 50%;">
                        <span style="font-size: smaller;"><u><b>Nomenchature</b></u></span><br>
                        <span style="font-size: smaller;"><b>Nama Barang</b></span>
                    </td>
                    <td colspan="3" style="text-align: left; width: 50%;">
                        <span style="font-size: smaller;"><u><b>Aircraft System</b></u></span><br>
                        <span style="font-size: smaller;"><b>Sistim Pesawat</b></span>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="text-align: left;">
                        <span style="font-size: smaller;">
                            <b>Part No.</b> &ensp; {{ $data->parachute->part_number ?? '-' }}
                        </span>
                    </td>
                    <td colspan="4" style="text-align: left;">
                        <span style="font-size: smaller;"><b>NSN</b></span>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="text-align: left;">
                        <span style="font-size: smaller;">
                            <b>Serial No.</b> &ensp; {{ $data->parachute->serial_number ?? '-' }}
                        </span>
                    </td>
                    <td colspan="2" style="text-align: left;">
                        <span style="font-size: smaller;"><u><b>Operating Hrs.</b></u></span><br>
                        <span style="font-size: smaller;"><b>Usia Pemakaian</b></span>

                    </td>
                    <td colspan="2" style="text-align: left;">
                        <span style="font-size: smaller;"><u><b>Date Removed</b></u></span><br>
                        <span style="font-size: smaller;"><b>Tgl. dilepas</b></span>
                    </td>
                </tr>

                <tr>
                    <!-- <td colspan="6" style="text-align: left;">
                        <span style="font-size: smaller;"><u><b>Discrepancy</b></u></span><br>
                        <span style="font-size: smaller;"><b>Catatan Kerusakan:</b></span><br>
                        @php
                        $allDescs = collect();
                        foreach ($data->items as $subitem) {
                        $allDescs = $allDescs->concat($subitem->itemDescriptions);
                        }
                        $limitedDescs = $allDescs->take(7);
                        @endphp
                        <br>
                        @if($limitedDescs->isNotEmpty())
                        <ul style="margin-top: 0; padding-left: 20px;">
                            @foreach($limitedDescs as $desc)
                            <li style="font-size: xx-small;">{{ $desc->description }}</li>
                            @endforeach
                            @if ($allDescs->count() > 7)
                            <li style="font-size: xx-small; font-style: italic;">dll...</li>
                            @endif
                        </ul>
                        @else @for ($i = 0; $i < 8; $i++) <br> @endfor @endif
                    </td> -->

                    <td colspan="6" style="text-align: left;">
                        <!-- <span style="width: 30%;"> <b>Diperbaiki Oleh</b> </span> <span> <b>:</b> </span><br> -->
                        <!-- <span style="width: 30%;"> <b>Diperiksa Oleh</b> </span> <span> <b>:</b> </span> -->

                        <div style="display: grid; grid-template-columns: 80px 15px auto; align-items: center; margin-bottom: 5px;">
                            <span><b>Diperbaiki Oleh</b></span> <span><b>:</b></span>
                            <span>{{ $data->repaired_by }}</span>
                        </div>

                        <div style="display: grid; grid-template-columns: 80px 15px auto; align-items: center;">
                            <span><b>Diperiksa Oleh</b></span> <span><b>:</b></span>
                            <span>{{ $data->person_in_charge }}</span>
                        </div>
                    </td>
                </tr>

                <!-- <tr>
                    <td colspan="3" style="text-align: center;">
                        <span style="font-size: smaller;"><b>FOR MAINTENANCE INSTRUCTION <br> PLEASE TURN OVER</b></span>
                    </td>
                    <td colspan="3" style="text-align: left;">
                        <span style="font-size: smaller;"><u><b>Doc.No.</b></u></span>
                    </td>
                </tr> -->
            </table>
        </div>
    </div>
</body>

<script>
    window.onload = function() {
        window.print();
    };
</script>

</html>