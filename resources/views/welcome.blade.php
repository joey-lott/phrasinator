<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>phrasinator</title>
    </head>
    <body>

      <form method="post" action="/generate">
        {{csrf_field()}}
        <div>
          font style:
          <select name="fontName">
            <option value="knockout.ttf">Knockout</option>
            <option value="GILLUBCD.ttf">Gill</option>
            <option value="KGSecondChancesSketch.ttf">Sketch</option>
            <option value="STENCIL.ttf">Stencil</option>
            <option value="jackport.ttf">Varsity</option>
          </select>
        </div>
        <div>
          font size: <input type="text" name="fontSize" value="-1"> (leave -1 to use default)
        </div>
        <div>
          <textarea name="quotes" cols="100" rows="30"></textarea>
        </div>
        <div>
          <button>Submit</button>
        </div>
      </form>
    </body>
</html>
