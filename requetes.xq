(: ================================================================
   FICHIER : requetes.xq
   PROJET  : Club Info_Tech — TP XML/XSD/XQuery
   ================================================================ :)

(: ─────────────────────────────────────────────────────────────
   Q1 — Liste complète des membres (1 pt)
   Affiche : id, nom complet, email, libellé catégorie
   ───────────────────────────────────────────────────────────── :)
let $doc := doc("club.xml")
return
<membres>
{
  (: Pour chaque membre du club :)
  for $m in $doc//membre
  (: Jointure : on récupère la catégorie dont l'id = categorieRef du membre :)
  let $cat := $doc//categorie[@id = $m/@categorieRef]
  return
    <membre id="{$m/@id}">
      <nomComplet>{$m/prenom/text()} {$m/nom/text()}</nomComplet>
      <email>{$m/email/text()}</email>
      <categorie>{$cat/@libelle/string()}</categorie>
    </membre>
}
</membres>

(: ─────────────────────────────────────────────────────────────
   Q2 — Liste des concours triés par date croissante (1 pt)
   Affiche : titre, date, coefficient, libellé catégorie
   ───────────────────────────────────────────────────────────── :)
let $doc := doc("club.xml")
return
<listeConcours>
{
  (: Pour chaque concours, trié par date :)
  for $c in $doc//concours/concours
  let $cat := $doc//categorie[@id = $c/@categorieRef]
  order by $c/@date ascending
  return
    <concours id="{$c/@id}">
      <titre>{$c/titre/text()}</titre>
      <date>{$c/@date/string()}</date>
      <coefficient>{$c/@coefficient/string()}</coefficient>
      <categorie>{$cat/@libelle/string()}</categorie>
    </concours>
}
</listeConcours>

(: ─────────────────────────────────────────────────────────────
   Q3 — Calcul des scores de chaque participant (2 pts)
   Formule : score = (complexite + tempsExecution) × coefficient
   Arrondi à 2 décimales avec round()
   ───────────────────────────────────────────────────────────── :)
let $doc := doc("club.xml")
return
<resultats>
{
  (: Pour chaque concours :)
  for $c in $doc//concours/concours
  let $coef := xs:decimal($c/@coefficient)
  return
    <concours titre="{$c/titre/text()}">
    {
      (: Pour chaque participant de ce concours :)
      for $p in $c//participant
      (: Jointure pour récupérer le nom du membre :)
      let $m     := $doc//membre[@id = $p/@membreRef]
      let $compl := xs:integer($p/complexite)
      let $temps := xs:integer($p/tempsExecution)
      (: Calcul du score arrondi à 2 décimales :)
      let $score := round(($compl + $temps) * $coef * 100) div 100
      return
        <participant>
          <nom>{$m/prenom/text()} {$m/nom/text()}</nom>
          <complexite>{$compl}</complexite>
          <tempsExecution>{$temps}</tempsExecution>
          <score>{$score}</score>
        </participant>
    }
    </concours>
}
</resultats>

(: ─────────────────────────────────────────────────────────────
   Q4 — Vainqueur de chaque concours (2 pts)
   En cas d'égalité : affiche tous les ex-aequo
   Indication : utilise max() et filtre [score = max(...)]
   ───────────────────────────────────────────────────────────── :)
let $doc := doc("club.xml")
return
<vainqueurs>
{
  for $c in $doc//concours/concours
  let $coef := xs:decimal($c/@coefficient)
  (: Calculer les scores de tous les participants :)
  let $scores :=
    for $p in $c//participant
    return ($p/complexite + $p/tempsExecution) * $coef
  (: Trouver le score maximum :)
  let $maxScore := max($scores)
  return
    <concours titre="{$c/titre/text()}">
    {
      (: Afficher tous les participants ayant le score max (ex-aequo) :)
      for $p in $c//participant
      let $m     := $doc//membre[@id = $p/@membreRef]
      let $score := ($p/complexite + $p/tempsExecution) * $coef
      where $score = $maxScore
      return
        <vainqueur>
          <nom>{$m/nom/text()}</nom>
          <prenom>{$m/prenom/text()}</prenom>
          <score>{$score}</score>
        </vainqueur>
    }
    </concours>
}
</vainqueurs>

(: ─────────────────────────────────────────────────────────────
   Q5 — Membres d'une catégorie donnée, triés alphabétiquement (2 pts)
   Variable $categorie : changer la valeur pour filtrer
   ───────────────────────────────────────────────────────────── :)
let $doc := doc("club.xml")
(: ← Changer cette valeur pour tester une autre catégorie :)
let $categorie := "Intelligence Artificielle"
return
<membres categorie="{$categorie}">
{
  (: Trouver l'id de la catégorie correspondant au libellé :)
  let $catId := $doc//categorie[@libelle = $categorie]/@id
  (: Filtrer et trier les membres :)
  for $m in $doc//membre[@categorieRef = $catId]
  order by $m/nom ascending, $m/prenom ascending
  return
    <membre id="{$m/@id}">
      <nom>{$m/nom/text()}</nom>
      <prenom>{$m/prenom/text()}</prenom>
      <email>{$m/email/text()}</email>
    </membre>
}
</membres>