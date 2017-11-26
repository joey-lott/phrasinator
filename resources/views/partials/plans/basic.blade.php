
<div class="well">
  <h2 class="planheading" style="text-align: center; color: red">Basic Plan</h2>
  <hr>
  <h3 style="text-align: center;">FEATURES</h3>
  <div style="border: solid 1px">
  <ul>
    <li>20 daily images</li>
    <li>Five fonts to choose from</li>
    <li>Generates high resolution images</li>
    <li>Transparent backgrounds</li>
    <li>Supports markup so you can apply colors</li>
    <li>Creates two images for each phrase: one ideal for light backgrounds and one ideal for dark backgrounds</li>
  </ul>
  </div>
  <h3 style="text-align: center;">All for only $9.99 per month!</h3>
  <form action="{{$planFormAction}}" method="post">
    {{csrf_field()}}
    <input type="hidden" name="planName" value="phrasinator-basic-monthly">
    <button class="btn btn-primary form-control">CHOOSE THIS PLAN</button>
  </form>
</div>
