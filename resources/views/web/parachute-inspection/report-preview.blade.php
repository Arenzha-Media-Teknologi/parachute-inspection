<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- <title>Laporan Pemeriksaan Parasut - Preview</title> -->
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
            border: 1px solid #000;
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
    <div style="padding-left: 20px;">
        <h1>{{ $title }}</h1>
        <!-- <button id="printPreviewBtn">Print Preview</button> -->
        <button id="generatePdfBtn">Download PDF</button>
        <button id="generateWordBtn">Download Word</button>
        <button id="generateExcelBtn">Download Excel</button>
        <div id="loading" style="display: none;">&nbsp;
            <span>Loading...</span>
        </div>
    </div>

    <div id="printArea">
        <div class="card-wrapper">
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
                                Tanggal &emsp;&emsp;&emsp;&emsp; {{ \Carbon\Carbon::parse($periode)->translatedFormat('F') }} {{$year}}
                            </p>
                            <div style="border-bottom: 2px solid black; width: 100%; margin-top: 5px; margin-left: auto;"></div>
                        </td>
                    </tr>
                </table>
                <h2>LAPORAN PEMERIKSAAN PARASUT</h2>
                <div class="info" style="margin-top: 50px;">
                    <table>
                        <tr>
                            <td>Tanggal Pemeriksaan :
                                <strong>
                                    {{ \Carbon\Carbon::parse($date_start)->format('d-m-Y') }}
                                    @if($date_end) s/d {{ \Carbon\Carbon::parse($date_end)->format('d-m-Y') }} @endif
                                </strong>
                            </td>
                        </tr>
                        @if($type)
                        <tr>
                            <td>Tipe Parasut : <strong> {{ $type }} </strong> </td>
                        </tr>
                        @endif
                        @if($status)
                        <tr>
                            <td>Status : <strong> {{ $status }} </strong> </td>
                        </tr>
                        @endif
                    </table>
                </div>
                <table class="inspection-table">
                    <thead>
                        <tr>
                            <th rowspan="2" style="text-align: center;">No.</th>
                            <th rowspan="2" style="text-align: center;">Kode Pemeriksaan</th>
                            <!-- <th style="text-align: center;">Tanggal</th> -->
                            <!-- <th style="text-align: left;">Tipe</th> -->
                            <th rowspan="2" style="text-align: center;">Part Number</th>
                            <th colspan="3" style="text-align: center;">Serial Number</th>
                            <th rowspan="2" rowspan="2" style="text-align: center;">Keterangan</th>
                        </tr>
                        <tr>
                            <th style="text-align: center;">Bag</th>
                            <th style="text-align: center;">Parasut<br>Utama</th>
                            <th style="text-align: center;">Parasut<br>Cadangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $item)
                        <tr>
                            <td style="text-align: center;">{{ $loop->iteration }}</td>
                            <td style="text-align: center;">{{ $item->number }}</td>
                            <!-- <td style="text-align: center;">{{ \Carbon\Carbon::parse($item->date)->format('d-m-Y') }}</td> -->
                            <!-- <td style="text-align: left;">{{ $item->parachute->type ?? '-' }}</td> -->
                            <td style="text-align: center;">{{ $item->parachute->part_number ?? '-' }}</td>
                            <td style="text-align: center;">{{ $item->parachute->serial_number ?? '-' }}</td>
                            <td style="text-align: center;">{{ $item->parachute->serial_number ?? '-' }}</td>
                            <td style="text-align: center;">{{ $item->parachute->serial_number ?? '-' }}</td>
                            <!-- <td style="text-align: left;">{{ $item->description }}</td> -->
                            <td style="text-align: left;">
                                @foreach($item->items as $subitem)

                                @php
                                $utamaDescs = $subitem->itemDescriptions->where('type', 'utama');
                                @endphp
                                @if($utamaDescs->isNotEmpty())
                                <div><strong>Utama:</strong></div>
                                <ul style="margin-top: 0; padding-left: 20px;">
                                    @foreach($utamaDescs as $desc)
                                    <li>{{ $desc->description }}</li>
                                    @endforeach
                                </ul>
                                @endif

                                @php
                                $cadanganDescs = $subitem->itemDescriptions->where('type', 'cadangan');
                                @endphp
                                @if($cadanganDescs->isNotEmpty())
                                <div><strong>Cadangan:</strong></div>
                                <ul style="margin-top: 0; padding-left: 20px;">
                                    @foreach($cadanganDescs as $desc)
                                    <li>{{ $desc->description }}</li>
                                    @endforeach
                                </ul>
                                @endif

                                @endforeach
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align: center;">Data tidak tersedia</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

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
    document.getElementById('printPreviewBtn').addEventListener('click', function() {
        var printContents = document.getElementById('printArea').innerHTML;
        var originalContents = document.body.innerHTML;

        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
    });


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

        let url = "{{ route('parachute-inspection.reportPdf') }}" + "?date_start=" + encodeURIComponent(date_start) + "&periode=" + encodeURIComponent(periode);
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
                            a.download = "laporan_inspeksi_parasut.pdf";
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
                alert('Terjadi kesalahan saat membuat PDF.');
            })
            .finally(() => {
                btn.disabled = false;
                loading.style.display = 'none';
            });
    });

    document.getElementById('generateWordBtn').addEventListener('click', function() {
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

        let url = "{{ route('parachute-inspection.reportWord') }}" + "?date_start=" + encodeURIComponent(date_start) + "&periode=" + encodeURIComponent(periode);
        if (date_end) {
            url += "&date_end=" + encodeURIComponent(date_end);
        }
        if (type) {
            url += "&type=" + encodeURIComponent(type);
        }
        if (status) {
            url += "&status=" + encodeURIComponent(status);
        }
        fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    date_start: date_start,
                    periode: periode,
                    date_end: date_end
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.url) {
                    fetch(data.url)
                        .then(res => res.blob())
                        .then(blob => {
                            const blobUrl = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = blobUrl;
                            a.download = "laporan_inspeksi_parasut.docx";
                            document.body.appendChild(a);
                            a.click();
                            a.remove();
                            window.URL.revokeObjectURL(blobUrl);
                        });
                } else {
                    alert('Gagal membuat Word.');
                }
            })
            .catch(() => {
                alert('Terjadi kesalahan saat membuat Word.');
            })
            .finally(() => {
                btn.disabled = false;
                loading.style.display = 'none';
            });
    });

    document.getElementById('generateExcelBtn').addEventListener('click', function() {
        const btn = this;
        const loading = document.getElementById('loading'); // kalau ada indikator loading
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
        if (loading) loading.style.display = 'inline';

        let url = "{{ route('parachute-inspection.reportExcel') }}" + "?date_start=" + encodeURIComponent(date_start) + "&periode=" + encodeURIComponent(periode);
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
            .then(response => {
                if (!response.ok) throw new Error('Gagal download Excel');
                return response.blob();
            })
            .then(blob => {
                const blobUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = blobUrl;
                a.download = "laporan_inspeksi_parasut.xlsx";
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(blobUrl);
            })
            .catch(() => alert('Terjadi kesalahan saat membuat Excel'))
            .finally(() => {
                btn.disabled = false;
                if (loading) loading.style.display = 'none';
            });
    });
</script>


</html>