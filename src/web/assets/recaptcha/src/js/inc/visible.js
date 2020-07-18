export const isVisible = function(el) {
    // Display and visibility vary per browser and must be sought in different ways depending on the browser
    var t1 = el.currentStyle ? el.currentStyle.visibility : getComputedStyle(el, null).visibility;
    var t2 = el.currentStyle ? el.currentStyle.display : getComputedStyle(el, null).display;
   
    // If either of these are true, then the element is not visible
    if (t1 === 'hidden' || t2 === 'none') {
        return false;
    }
    
    // This regex is used to scan the parent nodes all the way up to the body element
    while (!(/body/i).test(el)) {
        // Get the next parent node
        el = el.parentNode;
        
        // Grab the values, if available, 
        t1 = el.currentStyle ? el.currentStyle.visibility : getComputedStyle(el, null).visibility;
        t2 = el.currentStyle ? el.currentStyle.display : getComputedStyle(el, null).display;
        
        if (t1 === 'hidden' || t2 === 'none') {
            return false;
        }
    }

    // If all scans are not successful, then the element is visible
    return true;
};
