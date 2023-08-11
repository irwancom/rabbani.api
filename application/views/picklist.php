<style>
    @page { margin: 10mm; }
    table {
        border-collapse: collapse;
        width: 100%;
    }

    table#picklist, table#picklist th, table#picklist td {
        border: 1px solid #000;
        vertical-align: middle;
    }
</style>

<table>
    <tr>
        <td style="width:50%;font-size:18px;">Rabbani Mall Online</td>
        <td rowspan="2" style="text-align:right;">
            <img src="<?= $picklistCodeSrc ?>">
            <div><?= $picklistCode ?></div>
        </td>
    </tr>
    <tr>
        <td style="font-size: 24px;font-weight:bold;">
            Pengambilan Barang
        </td>
    </tr>
</table>
<br>
<table id="picklist">
    <th>
        <tr>
            <th style="width:5%;">NO</th>
            <th style="width:37%;">BARANG</th>
            <th style="width:20%;">NO PESANAN</th>
            <th style="width:12%;">LOKASI/RAK</th>
            <th style="width:11%;">QTY PESAN</th>
            <th style="width:5%;">UNIT</th>
            <th style="width:10%;">QTY AMBIL</th>
        </tr>
    </th>
    <tbody>
        <?php $no = 1; foreach ($items as $item): ?>
            <tr>
                <td rowspan="2" style="text-align:center;"><?= $no++ ?></td>
                <td rowspan="2"><?= $item['item_name'] ?></td>
                <td rowspan="2"><?= $item['salesorder_no'] ?></td>
                <td style="border-bottom:1px solid #000;">
                    <?= $item['location_name'] ?>
                </td>
                <td rowspan="2" style="text-align:right;"><?= $item['sales_qty'] ?></td>
                <td rowspan="2"><?= $item['unit'] ?></td>
                <td rowspan="2"></td>
            </tr>
            <tr>
                <td><?= isset($item['rack_code']) || !empty($item['rack_code']) ? $item['rack_code'] : '-' ?></td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>
