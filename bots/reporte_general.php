<?php
  require "../includes/functions.php";
  
  $headTabla = connectToDb("get_columns","general",null,null,null,null);
  $bodyTabla = connectToDb("all","general",null,null,null,null);
?>
<html>
  <head>
    <link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <!--  <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css"> -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/jq-3.3.1/jszip-2.5.0/dt-1.10.18/b-1.5.6/b-html5-1.5.6/datatables.min.css"/>
  </head>
  <body>
  <table id="tabla" class="display" style="width:100%">
    <thead>
        <tr>
          <?php foreach($headTabla as $headItem): ?>
            <th><?php echo $headItem; ?></th>
          <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach($bodyTabla as $key => $bodyItem): ?>
          <tr>
            <?php foreach($headTabla as $headItem): ?>
              <td><?php echo $bodyItem[$headItem]; ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
        
    </tbody>
    <tfoot>
      <tr>
        <?php foreach($headTabla as $headItem): ?>
          <th><?php echo $headItem; ?></th>
        <?php endforeach; ?>
      </tr>
    </tfoot>
  </table>

  <script
  src="https://code.jquery.com/jquery-3.4.1.min.js"
  integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
  crossorigin="anonymous"></script>
    <!-- <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script> -->
    <script type="text/javascript" src="https://cdn.datatables.net/v/dt/jq-3.3.1/jszip-2.5.0/dt-1.10.18/b-1.5.6/b-html5-1.5.6/datatables.min.js"></script>
    <script>
      $(document).ready( function () {
        $("#tabla").DataTable({
          pageLength: 200,
          dom: "Bfrtip",
          buttons: [
            {
                extend: "csv",
                text: "Descargar datos",
                filename: "reporte",
                exportOptions: {
                    modifier: {
                        search: "none"
                    }
                }
            }
          ]
        });
      });
    </script>
  </body>
</html>