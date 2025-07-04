<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- <title>Lampiran Pemeriksaan Parasut - Preview</title> -->
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
            /* background-color: #f9f9f9; */
            background-color: #fff;
            /* margin: 20px; */
        }

        .card-wrapper {
            padding: 20px;
            /* margin halaman */
            /* height: 100vh; */
            box-sizing: border-box;
            page-break-after: always;
            background-color: #fff;
        }

        .card {
            page-break-inside: avoid;
            break-inside: avoid;
            /* border: 2px solid green; */
            /* border-radius: 8px; */
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

    <style>
        .print-signature {
            display: none;
        }

        @media print {
            .print-signature {
                display: block;
            }

            button {
                display: none;
            }
        }
    </style>
</head>

<body>
    <!-- <div style="padding-left: 40px;"> -->
    <div style="padding-left: 20px;">
        <h1>{{ $title }}</h1>
        <!-- <button id="printPreviewBtn">Print Preview</button> -->
        <button id="generatePdfBtn">Download PDF</button>
        <button id="generateWordBtn">Download Word</button>
        <div id="loading" style="display: none;">&nbsp;
            <span>Loading...</span>
        </div>
    </div>

    <div id="printArea">
        <div class="card-wrapper">
            <!-- <div class="card-body" style="padding-left: 20px; padding-right: 20px;"> -->
            <div class="card">
                <table style="width: 100%; margin-bottom: 50px;">
                    <tr>
                        <td style="width: 30%; vertical-align: top;">
                            <p style="margin: 0; text-align: center; font-weight: bold; font-size: small;">DEPO PEMELIHARAAN 70</p>
                            <p style="margin: 0; text-align: center; font-weight: bold; font-size: small;">SATUAN PEMELIHARAAN 72</p>
                            <div style="border-bottom: 2px solid black; width: 100%; margin-top: 5px;"></div>
                        </td>
                        <td style="width: 35%; vertical-align: top;"> </td>
                        <td style="width: 30%; text-align: right; vertical-align: top;">
                            <p style="margin: 0; text-align: center; font-weight: bold; font-size: small;">Lampiran Nota Dinas Dansathar 72</p>
                            @php
                            $date = \Carbon\Carbon::parse($periode);
                            $year = $date->format('Y');
                            $month = (int) $date->format('m');

                            $romawiBulan = [ '', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII' ];
                            $bulan_romawi = $romawiBulan[$month];
                            @endphp
                            <p style="margin: 0; text-align: center; font-weight: bold; font-size: small;">Nomor B/ND- &emsp;&nbsp; /{{$bulan_romawi}}/{{$year}}/Sathar 72</p>
                            @php
                            \Carbon\Carbon::setLocale('id');
                            @endphp
                            <p style="margin: 0; text-align: center; font-weight: bold; font-size: small;">
                                Tanggal &emsp;&emsp;&emsp;&emsp;&emsp; {{ \Carbon\Carbon::parse($periode)->translatedFormat('F') }} {{$year}}
                            </p>
                            <div style="border-bottom: 2px solid black; width: 100%; margin-top: 5px; margin-left: auto;"></div>
                        </td>
                    </tr>
                </table>

                <h2>DOKUMENTASI KERUSAKAN PARASUT</h2>
                <div style="border-bottom: 2px solid black; width: 35%; margin-top: 5px; margin-left: auto; margin-right: auto; margin-bottom: 30px;"></div>

                <div class="text-center" style="padding-bottom: 50px; display: flex; justify-content: center;">
                    <table class="inspection-table" style="width: 100%;">
                        <tbody>
                            @forelse($data as $item)
                            {{-- Baris nomor, PN/SN --}}
                            <tr>
                                <td class="text-center" style="font-size: medium; width: 5%;"><b>{{ $loop->iteration }}.</b></td>
                                <td style="text-align: left; font-size: medium;">
                                    <b>PN : </b>{{ $item['parachute']['part_number'] ?? '-' }}
                                    &ensp;
                                    <b>SN : </b>{{ $item['parachute']['serial_number'] ?? '-'}}
                                </td>
                                <td></td>
                            </tr>

                            @foreach($item['items'] as $subitem)
                            <tr>
                                <td></td>
                                <td style="text-align: left; font-size: medium; padding-bottom: 30px">
                                    @php
                                    $path = storage_path('app/public/' . $subitem['image_url']);
                                    if (file_exists($path)) {
                                    $type = pathinfo($path, PATHINFO_EXTENSION);
                                    $data = file_get_contents($path);
                                    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                                    } else {
                                    $base64 = null;
                                    }
                                    @endphp

                                    @if($base64)
                                    <img src="{{ $base64 }}" style="max-width: 400px; max-height: 200px;" alt="Preview Image" />
                                    @else
                                    <span class="text-muted">– Gambar tidak ditemukan –</span>
                                    @endif
                                </td>

                                <td style="text-align: left; font-size: medium;">
                                    <b>KERUSAKAN :</b><br>
                                    @php
                                    $descs = $subitem['item_descriptions'] ?? [];
                                    $utamaDescs = array_filter($descs, fn($d) => strtolower($d['type'] ?? '') === 'utama');
                                    $cadanganDescs = array_filter($descs, fn($d) => strtolower($d['type'] ?? '') === 'cadangan');
                                    @endphp

                                    @if(count($utamaDescs))
                                    <p>
                                        <strong>Utama:</strong>
                                    <ul style="margin: .25em 0 .5em 1.25em; padding: 0;">
                                        @foreach($utamaDescs as $d)
                                        <li>{{ $d['description'] }}</li>
                                        @endforeach
                                    </ul>
                                    </p>
                                    @endif
                                    @if(count($cadanganDescs))
                                    <p>
                                        <strong>Cadangan:</strong>
                                    <ul style="margin: .25em 0 .5em 1.25em; padding: 0;">
                                        @foreach($cadanganDescs as $d)
                                        <li>{{ $d['description'] }}</li>
                                        @endforeach
                                    </ul>
                                    </p>
                                    @endif

                                    @if(!count($descs))
                                    <p>
                                        <span class="text-muted">– Tidak ada deskripsi –</span>
                                    </p>
                                    @endif
                                    <!-- <p>
                                    <b>STATUS :</b><br>
                                    @if( $subitem['status'] == 1 )
                                <p><b> <span style="color: green;"> Serviceable </span> </b> </p>
                                @else
                                <p><b> <span style="color: red;"> Unserviceable </span> </b> </p>
                                @endif
                                </p> -->
                                </td>
                            </tr>
                            @endforeach

                            @empty
                            <tr>
                                <td colspan="3" class="text-center">Data tidak tersedia</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Kolom Tanda Tangan -->
                <!-- Tabel Tanda Tangan (Hanya muncul saat print) -->
                <div class="print-signature">
                    <table class="signature-table" style="width: 100%; margin-top: 50px;">
                        <tr>
                            <td style="width: 50%; text-align: left; padding-left: 50px;">
                                <p>Mengetahui,</p>
                                <p>Dansathar 72</p>
                                <br><br><br><br>
                                <p style="text-decoration: underline; font-weight: bold;">[NAMA PEJABAT]</p>
                                <p>[Pangkat, NRP]</p>
                            </td>
                            <td style="width: 50%; text-align: right; padding-right: 50px;">
                                <p>Yang Membuat,</p>
                                <p>Petugas Pemeriksa</p>
                                <br><br><br><br>
                                <p style="text-decoration: underline; font-weight: bold;">[NAMA PETUGAS]</p>
                                <p>[Pangkat, NRP]</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

<script>
    // document.getElementById('printPreviewBtn').addEventListener('click', function() {
    //     var printContents = document.getElementById('printArea').innerHTML;
    //     var originalContents = document.body.innerHTML;

    //     document.body.innerHTML = printContents;
    //     window.print();
    //     document.body.innerHTML = originalContents;
    // });


    document.getElementById('generatePdfBtn').addEventListener('click', function() {
        const btn = this;
        const loading = document.getElementById('loading');
        let date_start = "{{ request('date_start') }}";
        let periode = "{{ request('periode') }}";
        let date_end = "{{ request('date_end') ?? '' }}";
        let type = "{{ request('type') ?? '' }}";
        let status = "{{ request('status') ?? '' }}";

        if (!date_start) {
            alert('Tanggal mulai harus diisi');
            return;
        }
        if (!periode) {
            alert('Periode laporan harus diisi');
            return;
        }
        btn.disabled = true;
        loading.style.display = 'inline';

        let url = "{{ route('parachute-inspection.reportAttachmentPdf') }}" + "?date_start=" + encodeURIComponent(date_start) + "&periode=" + encodeURIComponent(periode);
        if (date_end) {
            url += "&date_end=" + encodeURIComponent(date_end);
        }
        if (type) {
            url += "&type=" + encodeURIComponent(type);
        }
        if (status) {
            url += "&status=" + encodeURIComponent(status);
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.url) {
                    // Fetch the actual file as blob
                    fetch(data.url)
                        .then(res => res.blob())
                        .then(blob => {
                            const blobUrl = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = blobUrl;
                            a.download = "dokumentasi_kerusakan_parasut.pdf";
                            document.body.appendChild(a);
                            a.click();
                            a.remove();
                            window.URL.revokeObjectURL(blobUrl);
                        });
                } else {
                    alert('Gagal membuat PDF.');
                }
            })
            .catch(() => {
                console.log('Creating PDF Error');
                var printContents = document.getElementById('printArea').innerHTML;
                var originalContents = document.body.innerHTML;
                window.onafterprint = function() {
                    location.reload();
                };
                document.body.innerHTML = printContents;
                window.print();
                document.body.innerHTML = originalContents;
                // alert('Terjadi kesalahan saat membuat PDF.');
            })
            .finally(() => {
                btn.disabled = false;
                loading.style.display = 'none';
            });
    });

    document.getElementById('generateWordBtn').addEventListener('click', function() {
        const date_start = "{{ request('date_start') }}";
        const periode = "{{ request('periode') }}";
        const date_end = "{{ request('date_end') ?? '' }}";
        const type = "{{ request('type') ?? '' }}";
        const status = "{{ request('status') ?? '' }}";

        if (!date_start) return alert('Tanggal mulai harus diisi');
        if (!periode) return alert('Periode laporan harus diisi');

        // Buat form dinamis lalu submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ route('parachute-inspection.reportAttachmentWord') }}";
        form.target = '_blank';

        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        form.innerHTML = `
        <input type="hidden" name="_token" value="${token}">
        <input type="hidden" name="date_start" value="${date_start}">
        <input type="hidden" name="periode" value="${periode}">
        <input type="hidden" name="date_end" value="${date_end}">
        <input type="hidden" name="type" value="${type}">
        <input type="hidden" name="status" value="${status}">
    `;

        document.body.appendChild(form);
        form.submit();
        form.remove();
    });
</script>


</html>