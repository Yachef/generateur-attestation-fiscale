<?php
    require_once "vendor/autoload.php";
    use Dompdf\Dompdf;
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    class Client{

            public function __construct($ide,$nom,$prenom,$adresse,$cp,$ville){ 
                $this->id = $ide;
                $this->nom = $nom;
                $this->prenom = $prenom;
                $this->adresse = $adresse;
                $this->cp = $cp;
                $this->ville = $ville;
                $this->montant_total = 0;
                $this->interventions  = "";
                $this->cesu = 0;
            } 
    }
    function add_intervention($num,$nom_intervenant,$prenom_intervenant,$nb_heures,$prix,$date){
        $date = date_create($date);
        $date = date_format($date, 'd/m/Y');
        if($nb_heures > 0){
            if($nb_heures == 1){
                $heures = "heure";
            }
            else{
                $heures = "heures";
            }
            $datas = str_replace("NUM_IDENTIFICATION",strval(round($num)),$GLOBALS["INTERVENANT_HEURES"]);
            $datas = str_replace("NB_HEURES",strval(round($nb_heures))." ".$heures,$datas);
            $datas = str_replace("DATE",$date,$datas);
            $datas = str_replace("PRENOM_INTERVENANT",$prenom_intervenant,$datas);
            $datas = str_replace("NOM_INTERVENANT",$nom_intervenant,$datas);
            $datas = str_replace("PRIX",number_format(strval(round($prix/$nb_heures,2)),2),$datas);
            return $datas;
        }    
        else{
            $datas = str_replace("NUM_IDENTIFICATION",strval(round($num)),$GLOBALS["INTERVENANT_FORFAIT"]);
            $datas = str_replace("PRIX",number_format(strval(round($prix,2)),2),$datas);
            $datas = str_replace("DATE",$date,$datas);
            $datas = str_replace("PRENOM_INTERVENANT",$prenom_intervenant,$datas);
            $datas = str_replace("NOM_INTERVENANT",$nom_intervenant,$datas);
            return $datas;
        }
    }
    $GLOBALS["INTERVENANT_HEURES"] = "<small style = 'font-weight:bold;'>NUM_IDENTIFICATION, NOM_INTERVENANT PRENOM_INTERVENANT, NB_HEURES, le DATE</small><br>
                    <small>Prix horaire de la prestation : PRIX €</small><br>";
    $GLOBALS["INTERVENANT_FORFAIT"] = "<small style = 'font-weight:bold;'>NUM_IDENTIFICATION, NOM_INTERVENANT PRENOM_INTERVENANT, le DATE</small><br>
                        <small> Prix forfaitaire de la prestation : PRIX €</small><br>";
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $SEXE_SIGNATAIRE = $_POST["sexe"];
        $NOM_ENTREPRISE = $_POST["nom_entreprise"];
        $ADRESSE_ENTREPRISE = $_POST["adresse"];
        $CP_ENTREPRISE = $_POST["cp"];
        $VILLE_ENTREPRISE = $_POST["ville"];
        $NOM_SIGNATAIRE = $_POST["nom"];
        $PRENOM_SIGNATAIRE = $_POST["prenom"];
        $ROLE_SIGNATAIRE = $_POST["role"];
        $DATE_SIGNATURE = $_POST["date_signature"];
        $NUMERO_AGREMENT = $_POST["num_agrement"];
        $DATE_AGREMENT = $_POST["date_agrement"];
        $EMAIL = $_POST["email"];
        $ANNEE = "2019";

        $NOM_SIGNATAIRE = $NOM_SIGNATAIRE." ". $PRENOM_SIGNATAIRE;
        $soussigne = ($SEXE_SIGNATAIRE == "M" ?"soussigné" : "soussignée");
        $DATE_SIGNATURE = date('d/m/Y', strtotime($DATE_SIGNATURE));
        $DATE_AGREMENT = date('d/m/Y', strtotime($DATE_AGREMENT));

        $uploaded_filename = $_FILES['userfile']['name'];
        $ext = pathinfo($uploaded_filename, PATHINFO_EXTENSION);
        if($_FILES["userfile"]["error"] > 0){
            echo("<script>alert('Erreur lors du chargement du fichier. Veuillez réessayer. Si cela ne fonctionne toujours pas contacter : yachef.h@gmail.com')</script>");
        }elseif ($ext != "xlsx") {
            echo ("<script>alert('Extension du fichier non prise en charge. Veuillez convertir votre fichier en .csv ou .xlsx')</script>");
        }else{
            if ( $workbook = SimpleXLSX::parse($_FILES["userfile"]["tmp_name"]) ) {
                $clients = $workbook->rows(0);
                foreach($clients as $key=>$int){
                    if($int[0] == ""){
                        unset($clients[$key]);
                    }
                }
                $intervenants = $workbook->rows(1);
                foreach($intervenants as $key=>$int){
                    if($int[0] == ""){
                        unset($intervenants[$key]);
                    }
                }
                $interventions = $workbook->rows(2);
                foreach($interventions as $key=>$int){
                    if($int[0] == ""){
                        unset($interventions[$key]);
                    }
                }
                array_shift($clients);
                array_shift($intervenants);
                array_shift($interventions);
                array_multisort( array_column($clients, 0), SORT_ASC, $clients );
                array_multisort( array_column($intervenants, 0), SORT_ASC, $intervenants );
                array_multisort( array_column($interventions, 0), SORT_ASC, $interventions );

                $customerList = [];
                $customersListId = [];
                try{
                    for ($i = 0;$i < sizeof($interventions);$i++){
                        try{
                            $idClient = round($interventions[$i][0]);
                            $idIntervenant = round($interventions[$i][1]);
                            $intervenant = $intervenants[$idIntervenant-1];
                        }catch(Exception $e){
                            echo "<script>alert('Erreur de mise en forme du document, contactez l'administrateur')</script>";
                        }
                            if(!in_array($idClient,$customersListId)){
                                array_push($customersListId,$idClient);
                                $client = $clients[$idClient-1];
                                $newClient = new Client($idClient,$client[1],$client[2],$client[3],strval(round($client[4])),$client[5]);
                                if($idClient == 1){
                                    $c = add_intervention($intervenant[3],$intervenant[1],$intervenant[2],$interventions[$i][3],$interventions[$i][4],$interventions[$i][2]);
                                }
                                $newClient->interventions .= add_intervention($intervenant[3],$intervenant[1],$intervenant[2],$interventions[$i][3],$interventions[$i][4],$interventions[$i][2]);
                                $newClient->cesu+= $interventions[$i][5];
                                $newClient->montant_total += $interventions[$i][4];
                                array_push($customerList,$newClient);
                            }
                            else{
                                foreach ($customerList as $x){
                                    if ($x->id == $idClient){
                                        $x->interventions .= add_intervention($intervenant[3],$intervenant[1],$intervenant[2],$interventions[$i][3],$interventions[$i][4],$interventions[$i][2]);
                                        $x->cesu+= $interventions[$i][5];
                                        $x->montant_total += $interventions[$i][4];
                                    break;
                                    }
                                }
                            }
                    }
                }catch(\Exception $e) {
                    echo "<script>alert('Erreur dans les données des interventions. Veuillez vous référer au tutoriel pour corriger les erreurs .$e->getMessage()')</script>";
                    exit;
                }
                $model_html = file_get_contents("model.html");
                $model_html = str_replace("SOUSSIGNE",$soussigne,$model_html);
                $model_html = str_replace("NOM_ENTREPRISE",$NOM_ENTREPRISE,$model_html);
                $model_html = str_replace("ADRESSE_ENTREPRISE",$ADRESSE_ENTREPRISE,$model_html);
                $model_html = str_replace("CP_ENTREPRISE",$CP_ENTREPRISE,$model_html);
                $model_html = str_replace("VILLE_ENTREPRISE",$VILLE_ENTREPRISE,$model_html);
                $model_html = str_replace("NOM_SIGNATAIRE",$NOM_SIGNATAIRE,$model_html);
                $model_html = str_replace("ROLE_SIGNATAIRE",$ROLE_SIGNATAIRE,$model_html);
                $model_html = str_replace("DATE_SIGNATURE",$DATE_SIGNATURE,$model_html);
                $model_html = str_replace("NUMERO_AGREMENT",$NUMERO_AGREMENT,$model_html);
                $model_html = str_replace("DATE_AGREMENT",$DATE_AGREMENT,$model_html);
                $model_html = str_replace("ANNEE",$ANNEE,$model_html);  

                $random_number = rand(1,10000);

                $zip = new ZipArchive;      
                $zipname = "Attestations_".$random_number.".zip";
                $result_code  = $zip->open($zipname,ZipArchive::CREATE|ZipArchive::OVERWRITE);
                $files_to_delete = [];
                for  ($i = 0 ; $i<sizeof($customerList);$i++){
                    $edited_model = $model_html;
                    $edited_model = str_replace("PRENOM_CLIENT",$customerList[$i]->prenom,$edited_model);
                    $edited_model = str_replace("NOM_CLIENT",$customerList[$i]->nom,$edited_model);
                    $edited_model = str_replace("ADRESSE_CLIENT",$customerList[$i]->adresse,$edited_model);
                    $edited_model = str_replace("CP_CLIENT",$customerList[$i]->cp,$edited_model);
                    $edited_model = str_replace("VILLE_CLIENT",$customerList[$i]->ville,$edited_model);
                    $edited_model = str_replace("MONTANT_TOTAL",number_format($customerList[$i]->montant_total,2),$edited_model);
                    $edited_model = str_replace("MONTANT_CESU",number_format($customerList[$i]->cesu,2),$edited_model);
                    $edited_model = str_replace("PARTIE_INTERVENANTS",$customerList[$i]->interventions,$edited_model);
                    // instantiate and use the dompdf class
                    $dompdf = new Dompdf();
                    $dompdf->loadHtml($edited_model);
                    // (Optional) Setup the paper size and orientation
                    $dompdf->setPaper('A4', 'landscape');
                    // Render the HTML as PDF
                    $dompdf->render();
                    // Output the generated PDF to Browser
                    $pdf_file = $dompdf->output();
                    $filename = "Attestation_". $customerList[$i]->nom . "_". $customerList[$i]->prenom."_".$i.'.pdf';
                    file_put_contents($filename, $pdf_file);
        
                    $zip->addFile($filename);
                    array_push($files_to_delete,$filename);
                }
                $zip->close();
                foreach( $files_to_delete as $file){
                    unlink($file);
                }
                $mail = new PHPMailer;
                $mail->setFrom('yachef.h@gmail.com', 'No Sleeping Boy');
                $mail->addReplyTo('yachef.h@gmail.com', 'No Sleeping Boy');
                $mail->addAddress($EMAIL);
                $mail->Subject = 'Vos attestations';
                $mail->isHTML(true);
                $mail->addAttachment($zipname);
                $mailContent = "<p>Bonjour, Veuillez trouver ci-jointes vos attestations compressées.</p>
                <p>Pour décompresser le fichier .zip, voici un tuto : https://www.youtube.com/watch?v=LWLLLIjxSOc</p>
                <p>Pour tout bug ou questions, il vous suffira de répondre à ce mail !</p>
                <p>Amicalement,</p>
                <p>Yac de No Sleeping Boy</p>";
                $mail->Body = $mailContent;

                // Send email
                if(!$mail->send()){
                    echo "<script>alert('L email n a pas pu être envoyé. Erreur: " . $mail->ErrorInfo."')</script>";
                    unlink($zipname);
                }else{
                    unlink($zipname);
                    header("Location: https://generateur-attestation.nosleepingboy.fr/mail.php");
                    exit;
                }
            } else {
                echo "<script>alert('Erreur lors de la conversion du fichier uploadé, contactez l'administrateur. Error code : ".SimpleXLSX::parseError()."')</script>";
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Générateur d'attestations fiscales</title>

    <link rel="icon" type="image/png" href="images/no_sleeping_boy.png" />
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css?family=Varela+Round&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>

<body id="generation-attestation">
    <div class = "d-flex align-items-center">
    <a href="https://nosleepingboy.fr">
            <img src="images/no_sleeping_boy.png" style="height:40px;margin:10px">
        </a>
        <a href="https://nosleepingboy.fr" style="color:black;">No Sleeping Boy</a>
        <a href = "mentions-legales.php" class = "mr-3"style = "margin-left: auto;color:black;"target = "_blank">Mentions Légales</a>
        <a href = "cgu.php" style = "color:black;" target = "_blank" class = "mr-3">CGU</a>
    </div>
    <h1 class="text-center">Générateur d'Attestations fiscales</h1>
    <div class="text-center container">
        <p class="mb-0">Générez les attestations fiscales de service à la personne en PDF pour TOUS vos clients en <span
                class="font-weight-bold">2 min</span> chrono !</p>
        <p class="text-danger font-weight-bold mb-0">Service 100% GRATUIT, SANS INSCRIPTION, et SANS AUCUN stockage de données !</p>

    </div>

    <section style="background-color:#EDEDEB">
        <h2 class="title">Tutoriel</h2>
        <h5 class  ="text-center text-primary font-weight-bold">NOUVEAUTE : Maintenant les attestations sont directement envoyées par email !</h5>
        <div class="d-flex justify-content-center container">
            <iframe width="520" height="375" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true" src="https://www.youtube.com/embed/RAfOjCEwUMg?autoplay=0">
            </iframe>
        </div>
        <div class = "d-flex justify-content-center container mt-3">
            <a class = "font-weight-bold" href="modele.xlsx" download>CLIQUER ICI POUR TELECHARGER LE MODELE</a>
        </div>
    </section>
    <section style = "background-color:#343C3E;">
    <div class = "text-center text-white" style= "margin:5px">
       Besoin d'un site internet ou autre projet informatique ? Contactez moi à l'adresse :  <strong><a href = "mailto:yachef.h@gmail.com" class = "text-white">yac@nosleepingboy.com</a></strong> !
    </div>
    </section>
    <section class = "mb-5">
        <h2 class="title">Le Générateur</h2>
        <p class="text-danger font-weight-bold text-center mb-0">ATTENTION : Il est conseillé de regarder le tutoriel afin de remplir ce formulaire correctement !</p>
        <p class = "mt-0 text-center">Nous ne pourrons en aucun cas être tenus responsables de la conformité des attestations générées. </p>

        <div class="container">
            <form method = "post" action = "#" enctype="multipart/form-data">
                <h4 class="font-italic mb-0">L'entreprise</h4>
                <p class="font-italic">Informations concernant l'entreprise de Service à la Personne</p>
                
                <div class="row form-group">
                    <div class="col">
                        <label for="nom_entreprise">Nom de l'entreprise</label>
                        <input type="text" class="form-control" name="nom_entreprise" aria-describedby="nom_e_Help"
                            required placeholder = "EXEMPLE SAS">
                    </div>
                    <div class="col">
                        <label for="num_agrement">Numéro d'agrément de Service à la Personne</label>
                        <input type="text" class="form-control" name="num_agrement" required placeholder = "SAPXXXXXXXXX">
                    </div>
                    <div class="col">
                        <label for="date_agrement">Date de l'agrément</label>
                        <input type="date" class="form-control" name="date_agrement" required>
                    </div>
                </div>
                
                <label for="adresse">Adresse postale de l'entreprise</label>
                <div class="form-group">
                    <div class="input-group">
                        <input type="text" class="form-control" name="adresse"
                            required placeholder = "20 Rue des Jeûneurs" />
                        <input type="number" class="form-control" required placeholder = "75015" name = "cp"/>
                        <input type="text" class="form-control" required placeholder = "Paris" name = "ville"/>
                    </div>
                </div>

                <h4 class="font-italic mb-0">Le signataire</h4>
                <p class="font-italic">Informations concernant le signataire des attestations</p>

                <div class="row">
                    <div class="col">
                        <label for="nom">Nom du signataire</label>
                        <input type="text" class="form-control" name="nom" aria-describedby="nomHelp"
                            required placeholder = "Dupont">
                    </div>
                    <div class="col">
                        <label for="prenom">Prénom du signataire</label>
                        <input type="text" class="form-control" name="prenom" required placeholder = "Jean">
                    </div>
                    <div class="col">
                        <label for="sexe">Sexe du signataire</label>
                        <select class="form-control" name="sexe" aria-describedby="sexeHelp">
                            <option value = "M">Homme</option>
                            <option value = "F">Femme</option>
                        </select>
                        <small name="sexeHelp" class="form-text text-muted">Utilisé pour la ponctuation au féminin si
                            nécessaire</small>
                    </div>
                </div>

                <div class="row form-group">
                <div class="col">
                        <label for="email">Email du signataire</label>
                        <input type="email" class="form-control" name="email"
                            required placeholder = "exemple@gmail.com">
                        <small name="emailHelp" class="form-text text-muted"> Email où seront envoyées les attestations</small>

                    </div>
                    <div class="col">
                        <label for="role">Fonction du signataire</label>
                        <input type="text" class="form-control" name="role"
                            required placeholder = "Président">
                    </div>
                    <div class="col">
                            <label for="date_signature">Date de signature</label>
                            <input type="date" class="form-control" name="date_signature" aria-describedby="dateHelp" required>
                            <small name="dateHelp" class="form-text text-muted">Date de la signature figurant sur les attestations</small>
                    </div>
                </div>
                
                <h4 class="font-italic mb-0">Les clients</h4>
                <p class="font-italic mb-0">Chargez le document contenant les données de vos clients<strong> (extension autorisée : xlsx)</strong></p>
                <p class = "font-italic text-danger">Regarder le tutoriel est vivement conseillé pour formater correctement ce document</p>
                
                <div class="form-group">
                    <input type="file" class="form-control-file" name="userfile">
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="check1" name = "check1" required>
                    <label class="form-check-label" for="check1">J'ai lu et j'accepte les <a href = "cgu.php" target = "_black">CGU</a></label>
                </div>
                <div class="d-flex justify-content-center">
                    <button type="submit" class="btn btn-lg btn-primary mb-5">Générer les attestations</button>
                </div>
            </form>
        </div>
    </section>
</body>
<footer class="fixed-bottom" style = "background-color:#343C3E;">
    <div class = "text-center text-white" style= "margin:5px">
       Un Bug ou une question ? Contactez moi à l'adresse : <strong><a href = "mailto:yachef.h@gmail.com" class = "text-white">yachef.h@gmail.com</a></strong> !
    </div>
</footer>
<script src="assets/jquery.min.js"></script>
<script src="assets/bootstrap/js/bootstrap.min.js"></script>
<script src="js/script.js"></script>
</html>