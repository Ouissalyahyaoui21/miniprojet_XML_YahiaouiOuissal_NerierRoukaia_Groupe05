<?php
declare(strict_types=1);
$xmlFile = realpath(__DIR__ . '/../club.xml');
$page    = (string)($_GET['page'] ?? '1');
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === '2') {
    $membreId   = trim($_POST['membreId']   ?? '');
    $concoursId = trim($_POST['concoursId'] ?? '');
    $complexite = (int)($_POST['complexite']     ?? -1);
    $temps      = (int)($_POST['tempsExecution'] ?? 0);

    if ($membreId && $concoursId && $complexite >= 0 && $complexite <= 100 && $temps > 0) {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->load($xmlFile);
        $xp = new DOMXPath($dom);
        $membre   = $xp->query("//membre[@id='$membreId']")->item(0);
        $concours = $xp->query("//concours[@id='$concoursId']")->item(0);
        if ($membre && $concours) {
            $catMembre   = $membre->getAttribute('categorieRef');
            $catConcours = $concours->getAttribute('categorieRef');
            if ($catMembre === $catConcours) {
                $existe = $xp->query("//concours[@id='$concoursId']//participant[@membreRef='$membreId']")->item(0);
                if (!$existe) {
                    $parts   = $xp->query("//concours[@id='$concoursId']/participants")->item(0);
                    $newPart = $dom->createElement('participant');
                    $newPart->setAttribute('membreRef', $membreId);
                    $newPart->appendChild($dom->createElement('complexite',     (string)$complexite));
                    $newPart->appendChild($dom->createElement('tempsExecution', (string)$temps));
                    $parts->appendChild($newPart);
                    $dom->save($xmlFile);
                    $message = ['type'=>'success','text'=>"Inscription reussie ! $membreId inscrit au concours $concoursId."];
                } else {
                    $message = ['type'=>'error','text'=>"Ce membre est deja inscrit a ce concours."];
                }
            } else {
                $message = ['type'=>'error','text'=>"Le membre ($catMembre) et le concours ($catConcours) n'ont pas la meme categorie."];
            }
        } else {
            $message = ['type'=>'error','text'=>"Membre ou concours introuvable."];
        }
    } else {
        $message = ['type'=>'error','text'=>"Remplissez tous les champs (complexite 0-100, temps > 0)."];
    }
}

$xml = simplexml_load_file($xmlFile);

function getLibelle(SimpleXMLElement $xml, string $catId): string {
    $res = $xml->xpath("//categorie[@id='$catId']");
    return $res ? (string)$res[0]['libelle'] : $catId;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Club Info_Tech</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
  <div class="header-inner">
    <div class="logo">
      <span class="logo-icon">&#9881;</span>
      <div>
        <h1>Club Info_Tech</h1>
        <p>Universite de Skikda &mdash; Licence 3 ISIL &mdash; Groupe 05</p>
      </div>
    </div>
    <nav>
      <a href="?page=1" class="<?= $page==='1'?'active':'' ?>">Concours</a>
      <a href="?page=2" class="<?= $page==='2'?'active':'' ?>">Inscription</a>
      <a href="?page=3" class="<?= $page==='3'?'active':'' ?>">Resultats</a>
      <a href="?page=4" class="<?= $page==='4'?'active':'' ?>">Requetes</a>
    </nav>
  </div>
</header>
<main>
<?php if ($message): ?>
<div class="alert alert-<?= $message['type'] ?>"><?= htmlspecialchars($message['text']) ?></div>
<?php endif; ?>

<?php if ($page==='1'): ?>
<div class="section-header"><h2>Liste des Concours</h2><p>Tries par date croissante</p></div>
<?php
$list = [];
foreach ($xml->concours->concours as $c)
    $list[] = ['id'=>(string)$c['id'],'titre'=>(string)$c->titre,'date'=>(string)$c['date'],
               'categorie'=>getLibelle($xml,(string)$c['categorieRef']),'coef'=>(string)$c['coefficient'],
               'nb'=>count($c->participants->participant)];
usort($list, fn($a,$b)=>strcmp($a['date'],$b['date']));?>
<div class="table-wrapper"><table>
  <thead><tr><th>ID</th><th>Titre</th><th>Date</th><th>Categorie</th><th>Coefficient</th><th>Participants</th></tr></thead>
  <tbody>
  <?php foreach ($list as $c): ?>
  <tr>
    <td><span class="badge"><?= htmlspecialchars($c['id']) ?></span></td>
    <td><?= htmlspecialchars($c['titre']) ?></td>
    <td><?= htmlspecialchars($c['date']) ?></td>
    <td><?= htmlspecialchars($c['categorie']) ?></td>
    <td><?= htmlspecialchars($c['coef']) ?></td>
    <td><?= $c['nb'] ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<div class="stats-grid">
  <div class="stat-card"><span class="stat-num"><?= count($xml->categories->categorie) ?></span><span class="stat-label">Categories</span></div>
  <div class="stat-card"><span class="stat-num"><?= count($xml->membres->membre) ?></span><span class="stat-label">Membres</span></div>
  <div class="stat-card"><span class="stat-num"><?= count($xml->concours->concours) ?></span><span class="stat-label">Concours</span></div>
</div>

<?php elseif ($page==='2'): ?>
<div class="section-header"><h2>Inscription a un Concours</h2><p>Membre inscrit dans club.xml via PHP/DOMDocument</p></div>
<div class="form-card">
  <form method="post" action="?page=2">
    <div class="form-grid">
      <div class="form-group">
        <label>Membre</label>
        <select name="membreId" required>
          <option value="">-- Choisir --</option>
          <?php foreach ($xml->membres->membre as $m): ?>
          <option value="<?= $m['id'] ?>"><?= $m['id'] ?> — <?= $m->prenom ?> <?= $m->nom ?> (<?= getLibelle($xml,(string)$m['categorieRef']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Concours</label>
        <select name="concoursId" required>
          <option value="">-- Choisir --</option>
          <?php foreach ($xml->concours->concours as $c): ?>
          <option value="<?= $c['id'] ?>"><?= $c['id'] ?> — <?= $c->titre ?> (<?= getLibelle($xml,(string)$c['categorieRef']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Complexite (0-100)</label>
        <input type="number" name="complexite" min="0" max="100" required placeholder="ex: 75">
      </div>
      <div class="form-group">
        <label>Temps d'execution (ms)</label>
        <input type="number" name="tempsExecution" min="1" required placeholder="ex: 150">
      </div>
    </div>
    <div class="info-box">Un membre ne peut s'inscrire qu'a un concours de <strong>sa propre categorie</strong>.</div>
    <button type="submit" class="btn-primary">Inscrire</button>
  </form>
</div>

<?php elseif ($page==='3'): ?>
<div class="section-header"><h2>Resultats des Concours</h2><p>score = (complexite + tempsExecution) &times; coefficient</p></div>
<?php $selId = (string)($_GET['concours'] ?? $xml->concours->concours[0]['id']); ?>
<div class="select-bar">
  <form method="get"><input type="hidden" name="page" value="3">
    <label>Concours :</label>
    <select name="concours" onchange="this.form.submit()">
      <?php foreach ($xml->concours->concours as $c): ?>
      <option value="<?= $c['id'] ?>" <?= $selId==(string)$c['id']?'selected':'' ?>><?= $c->titre ?> (<?= $c['date'] ?>)</option>
      <?php endforeach; ?>
    </select>
  </form>
</div>
<?php
$sel = $xml->xpath("//concours[@id='$selId']")[0] ?? null;
if ($sel):
  $coef = (float)$sel['coefficient'];
  $parts = [];
  foreach ($sel->participants->participant as $p) {
    $m = $xml->xpath("//membre[@id='".(string)$p['membreRef']."']")[0];
    $compl = (int)$p->complexite; $temps = (int)$p->tempsExecution;
    $parts[] = ['nom'=>(string)$m->prenom.' '.(string)$m->nom,'compl'=>$compl,'temps'=>$temps,'score'=>round(($compl+$temps)*$coef,2)];
  }
  $max = max(array_column($parts,'score'));
  usort($parts, fn($a,$b)=>$b['score']<=>$a['score']);
?><div class="concours-info-bar"><strong><?= htmlspecialchars((string)$sel->titre) ?></strong> &bull; Categorie : <?= getLibelle($xml,(string)$sel['categorieRef']) ?> &bull; Coefficient : <?= $coef ?></div>
<div class="table-wrapper"><table>
  <thead><tr><th>#</th><th>Participant</th><th>Complexite</th><th>Temps (ms)</th><th>Score</th><th>Statut</th></tr></thead>
  <tbody>
  <?php foreach ($parts as $i=>$p): $w=($p['score']===$max); ?>
  <tr class="<?= $w?'winner-row':'' ?>">
    <td><?= $i+1 ?></td><td><?= htmlspecialchars($p['nom']) ?></td>
    <td><?= $p['compl'] ?></td><td><?= $p['temps'] ?></td>
    <td><strong><?= $p['score'] ?></strong></td>
    <td><?= $w?'<span class="badge-winner">Vainqueur &#127942;</span>':'' ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>

<?php elseif ($page==='4'): ?>
<div class="section-header"><h2>Requetes Libres</h2><p>XPath sur club.xml</p></div>
<?php
$pre = ['q1'=>['label'=>'Q1 — Tous les membres','xpath'=>'//membre'],
        'q2'=>['label'=>'Q2 — Tous les concours','xpath'=>'//concours/concours'],
        'q3'=>['label'=>'Q3 — Tous les participants','xpath'=>'//participant'],
        'q4'=>['label'=>'Q4 — Toutes les categories','xpath'=>'//categorie'],
        'q5'=>['label'=>'Q5 — Membres categorie IA','xpath'=>'//membre[@categorieRef="C1"]']];
$sel2 = $_GET['query'] ?? 'q1';
$custom = trim($_GET['xpath'] ?? '');
$results = [];
$used = '';
if ($custom !== '') { $used=$custom; $r=@$xml->xpath($custom); $results=$r?:[];
} elseif (isset($pre[$sel2])) { $used=$pre[$sel2]['xpath']; $results=$xml->xpath($used)?:[]; }
?>
<div class="form-card">
  <form method="get"><input type="hidden" name="page" value="4">
    <div class="form-grid">
      <div class="form-group"><label>Requete predефinie</label>
        <select name="query" onchange="document.getElementById('xp').value='';this.form.submit()">
          <?php foreach ($pre as $k=>$q): ?>
          <option value="<?= $k ?>" <?= $sel2===$k&&!$custom?'selected':'' ?>><?= htmlspecialchars($q['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>XPath personnalise</label>
        <input type="text" name="xpath" id="xp" value="<?= htmlspecialchars($custom) ?>" placeholder="ex: //membre[@categorieRef='C2']">
      </div>
    </div>
    <button type="submit" class="btn-primary">Executer</button>
  </form>
</div>
<?php if ($used): ?><div class="results-count">Expression : <code><?= htmlspecialchars($used) ?></code> — <?= count($results) ?> resultat(s)</div><?php endif; ?>
<?php if (count($results)>0): ?>
<div class="xml-results">
  <?php foreach ($results as $node):
    $dn=$d=null; $dn=dom_import_simplexml($node); $d=new DOMDocument('1.0','UTF-8'); $d->formatOutput=true;
    $d->appendChild($d->importNode($dn,true)); ?>
  <pre class="xml-block"><?= htmlspecialchars($d->saveXML($d->documentElement)) ?></pre>
  <?php endforeach; ?>
</div>
<?php elseif ($used): ?><div class="info-box">Aucun resultat.</div><?php endif; ?>
<?php endif; ?>
</main>
<footer><p><strong>Club Info_Tech</strong> &mdash; TP XML/XSD/XQuery</p><p>Nerier Roukaia &amp; Yahiaoui Ouissal &mdash; Groupe 05 &mdash; Universite de Skikda</p></footer>
</body>
</html>