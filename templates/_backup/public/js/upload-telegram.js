function frUpload() {
    const form = document.getElementById("form");
    const file = form.file.files[0];
    if(file == null){
      alert("Vui lòng chọn file!");
      return;
    };
    if(file.size > maxFileSizeAllow){
      alert("File lớn hơn mức cho phép!");
      this.value = "";
      return;
    };

    $('#filesize').val(file.size);
    $('#filename').val(file.name);
    $('#dai').append('<img src="https://i.imgur.com/FH1qaEG.gif" width="25px" />');
    $('#buttonD').hide();

    const formData = new FormData();
    formData.append('document', file);

 fetch(UrlUpload, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
          $('#fileidtg').val(data.file_id);
      document.getElementById("form").submit();
    })
    .catch(error => {
        console.error('Error:', error);
    });
}