
<div class="well">
  <h2 class="planheading" style="text-align: center; color: red">Plus Plan</h2>
  <hr>
  <h3 style="text-align: center;">FEATURES</h3>
  <div style="border: solid 1px">
  <ul>
    <li>All the Basic plan features plus...</li>
    <li>Unlimited daily images</li>
    <li>More fonts</li>
    <li>Text alignment</li>
    <li>Line spacing control</li>
    <li>Add Pixabay images above or below text</li>
  </ul>
  </div>
  <h3 style="text-align: center;">All for only $19.99 per month!</h3>
  <form action="{{$planFormAction}}" method="post">
    {{csrf_field()}}
    <input type="hidden" name="planName" value="phrasinator-plus-monthly">
    <button class="btn btn-primary form-control">CHOOSE THIS PLAN</button>
  </form>
</div>
