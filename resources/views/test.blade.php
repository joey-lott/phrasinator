<div>
  <label>
    <input type="radio">
    <img src="https://cdn.pixabay.com/photo/2016/08/13/01/54/apple-1590131_150.png">
  </label>
</div>
<style>
  label > input {
    visibility: hidden;
    position: absolute;
  }
  label > input + img {
    cursor:pointer;
    border:2px solid transparent;
  }
  label > input:checked + img {
    border:2px solid #f00;
  }
</style>
