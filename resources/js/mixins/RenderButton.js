export default {

    props:{},
    methods:{
        renderButton: function () {
            if(!this.$attrs.lens)
                (this.$parent.$parent.$el).parentElement.nextElementSibling.nextElementSibling.appendChild(this.$el);
            else
                (this.$parent.$parent.$el).parentElement.previousElementSibling.appendChild(this.$el);
        }
    }

}
