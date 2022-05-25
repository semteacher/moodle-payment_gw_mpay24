import Ajax from 'core/ajax';


export const init = (component,
    paymentarea,
    itemid,
    tid) => {


    Ajax.call([{
        methodname: "paygw_mpay24_redirectpayment",
        args: {
            component,
            paymentarea,
            itemid,
            tid,
        },
        done: function(data) {
            location.href = data.url;
        }
    }]);

};