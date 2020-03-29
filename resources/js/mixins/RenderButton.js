export default {

    props:{},
    methods:{
        renderButton: function () {
            if(!this.$attrs.lens)
               return (this.$parent.$parent.$el).parentElement.nextElementSibling.nextElementSibling.appendChild(this.$el);
        }
    }

}
