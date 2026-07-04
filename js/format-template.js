function formatTemplate(dta, tmpl) {  
    var format = {  
        name: function(x) {  
            return x  
        }  
    };  
    return tmpl.replace(/{(\S+)}/g, function(m1, m2) {  
        if (!m2)  
            return "";  
        return (format && format[m2]) ? format[m2](dta[m2]) : dta[m2];  
    });  
}