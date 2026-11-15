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
    const csrfInput = form.querySelector('input[name="csrf_token"]');
    const headers = {};
    if (csrfInput && csrfInput.value && UrlUpload.indexOf('/media/upload') !== -1) {
        formData.append('csrf_token', csrfInput.value);
        headers['X-Requested-With'] = 'XMLHttpRequest';
    }

    fetch(UrlUpload, {
        method: 'POST',
        headers: headers,
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