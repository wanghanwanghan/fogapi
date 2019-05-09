// Set new default font family and font color to mimic Bootstrap's default styling
Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#858796';

// Pie Chart Example
var url ='/admin/security/ajax';

var data=
    {
        _token:$("input[name=_token]").val(),
        type  :'get_user_distribution'
    };

$.post(url,data,function (response) {

    var labels=[];
    var data=[];

    $.each(response,function(key,value)
    {
        labels.push(key);
        data.push(value);
    });

    var ctx = document.getElementById("myPieChart");
    var myPieChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: ['#e74a3b','#f6c23e','#1cc88a','#36b9cc','#4e73df'],
                hoverBackgroundColor: ['#e63a29','#f4b924','#06aa6f','#06afc8','#2454df'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: true,
                caretPadding: 10,
            },
            legend: {
                display: false
            },
            cutoutPercentage: 80,
        },
    });

},'json');