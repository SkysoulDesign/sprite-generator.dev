<?php require __DIR__ . '/vendor/autoload.php';

ini_set('display_errors', 1);

$step = isset($_POST["step"]) ? $_POST["step"] : false;

/**
 * If step one generate data Asset
 */
if ($step === "one") {

    $generator = new Skysoul\Generator($_POST);

    echo $generator->getDataSet();

    return;

}

/**
 * Generate Image Atlas
 */
if ($step === "two") {

    $generator = new Skysoul\Atlas($_POST, $_FILES['file']);
    $generator->generate();
    
    return;

}

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sprite Sheet Generator</title>
</head>

<!-- Jquery -->
<script src="js/jquery-1.12.3.min.js"></script>

<!-- Semantic ui -->
<link rel="stylesheet" type="text/css" href="semantic-ui/semantic.min.css">
<script src="semantic-ui/semantic.min.js"></script>

<!-- Select2 -->
<link href="select2/dist/css/select2.min.css" rel="stylesheet"/>
<script src="select2/dist/js/select2.min.js"></script>

<style>
    .ui.container {
        margin-top: 20px;
    }
</style>

<body>

<div class="ui container">

    <div class="ui top attached tabular menu">
        <a class="active item" data-tab="first">First</a>
        <a class="item" data-tab="second">Second</a>
        <a class="item" data-tab="third">Third</a>
    </div>


    <div class="ui bottom attached tab segment" data-tab="third">
        Third
    </div>

    <!-- First Step -->
    <div class="ui bottom attached active tab segment" data-tab="first">

        <form class="ui form" action="/" method="post">

            <input type="hidden" name="step" value="one">

            <div class="field">
                <label>Excluded Words</label>
                <select name="exclude[]" multiple class="ui dropdown">
                    <option>，, ,, \, , , “, ”, 。, ？, ：, ！, 、, —</option>
                </select>
            </div>

            <div class="field">
                <label>Injected Words</label>
                <select name="inject[]" multiple="" class="ui dropdown">
                    <option>abcdefghijklmnopqrstuvwxyz</option>
                    <option>1234567890</option>
                    <option>()?!#$%/@=:;-.</option>
                </select>
            </div>

            <div class="field">
                <label>Word List</label>
                <textarea name="text"></textarea>
            </div>

            <button class="ui button first" type="submit">Submit</button>
            <a class="ui button green right floated" href="character-template.psd">Download PSD</a>

        </form>

    </div>

    <!-- Second Step -->
    <div class="ui bottom attached tab segment" data-tab="second">
        <form class="ui form" action="/" method="post" enctype="multipart/form-data">

            <input type="hidden" name="step" value="two">

            <div class="field">
                <label>Zipped Folder</label>
                <input type="file" name="file">
            </div>

            <div class="field">
                <label>Width</label>
                <input type="text" name="x" placeholder="1024">
            </div>

            <div class="field">
                <label>Height</label>
                <input type="text" name="y" placeholder="1024">
            </div>

            <button class="ui button second" type="submit">Submit</button>
            <a class="ui button green right floated" href="character-template.psd">Download PSD</a>

        </form>
    </div>

</div>

<script type="text/javascript">

    $('select').select2({
        tags: true
    });

    $('.menu .item').tab();

    $('.ui.button.first').on('click', function () {
        $.tab('change tab', 'second');
    })

</script>

</body>

</html>